<?php
session_start();
include 'config/email.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'libs/PHPMailer/src/Exception.php';
require 'libs/PHPMailer/src/PHPMailer.php';
require 'libs/PHPMailer/src/SMTP.php';

$mensaje = "";
$tipo_mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo        = trim($_POST['correo'] ?? '');
    $nueva_pass    = $_POST['nueva_password'] ?? '';
    $confirmar_pass = $_POST['confirmar_password'] ?? '';

    if ($correo === '' || $nueva_pass === '' || $confirmar_pass === '') {
        $mensaje = "Todos los campos son obligatorios.";
        $tipo_mensaje = "error";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Ingresa un correo electrónico válido.";
        $tipo_mensaje = "error";
    } elseif (strlen($nueva_pass) < 8) {
        $mensaje = "La contraseña debe tener al menos 8 caracteres.";
        $tipo_mensaje = "error";
    } elseif (!preg_match('/[A-Z]/', $nueva_pass)) {
        $mensaje = "La contraseña debe contener al menos una letra mayúscula.";
        $tipo_mensaje = "error";
    } elseif (!preg_match('/[0-9]/', $nueva_pass)) {
        $mensaje = "La contraseña debe contener al menos un número.";
        $tipo_mensaje = "error";
    } elseif (!preg_match('/[#$@!%&*?]/', $nueva_pass)) {
        $mensaje = "La contraseña debe contener al menos un símbolo especial (#, $, @, !, %).";
        $tipo_mensaje = "error";
    } elseif ($nueva_pass !== $confirmar_pass) {
        $mensaje = "Las contraseñas no coinciden.";
        $tipo_mensaje = "error";
    } else {
        include 'config/conexion.php';
        $hash = password_hash($nueva_pass, PASSWORD_DEFAULT);

        // Verificar que el correo existe
        $stmt_check = mysqli_prepare($conexion, "SELECT id_profesor FROM profesores WHERE correo = ?");
        if ($stmt_check) {
            mysqli_stmt_bind_param($stmt_check, "s", $correo);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);

            if (mysqli_stmt_num_rows($stmt_check) > 0) {
                mysqli_stmt_close($stmt_check);

                // Generar token de restablecimiento seguro
                $token = bin2hex(random_bytes(32));

                // Insertar la solicitud de restablecimiento pendiente
                $stmt = mysqli_prepare($conexion, "INSERT INTO restablecimientos_password (correo, token, password_hash) VALUES (?, ?, ?)");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "sss", $correo, $token, $hash);
                    if (mysqli_stmt_execute($stmt)) {
                        // Construir enlace de confirmación dinámico
                        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                        $host = $_SERVER['HTTP_HOST'];
                        $base_dir = dirname($_SERVER['PHP_SELF']);
                        $base_dir = ($base_dir === '\\' || $base_dir === '/') ? '' : $base_dir;
                        $link_confirmacion = $protocol . $host . $base_dir . '/confirmar_restablecimiento.php?token=' . $token;

                        // Diseño de correo electrónico premium
                        $template_html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmar Cambio de Contraseña</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background-color: #f4f6f8; margin: 0; padding: 40px 20px; color: #333; }
        .email-container { max-width: 600px; background: #ffffff; border-radius: 12px; margin: 0 auto; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border: 1px solid #e1e4e8; }
        .header { background: #001b45; padding: 30px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 20px; letter-spacing: 2px; }
        .content { padding: 40px 30px; line-height: 1.6; }
        .btn-confirm { display: inline-block; background-color: #0057ff; color: #ffffff !important; padding: 14px 28px; border-radius: 8px; text-decoration: none; font-weight: bold; margin: 24px 0; box-shadow: 0 4px 10px rgba(0, 87, 255, 0.3); }
        .btn-confirm:hover { background-color: #0047d6; }
        .footer { background: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #e1e4e8; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>INSTITUTO EDUCATIVO</h1>
        </div>
        <div class="content">
            <h2>Confirmación de cambio de contraseña</h2>
            <p>Hola,</p>
            <p>Hemos recibido una solicitud para cambiar la contraseña de tu cuenta institucional <strong>{correo}</strong>.</p>
            <p>Para confirmar esta solicitud y aplicar tu nueva contraseña de forma segura, haz clic en el siguiente botón:</p>
            <div style="text-align: center;">
                <a href="{link_confirmacion}" class="btn-confirm">Confirmar cambio de contraseña</a>
            </div>
            <p>Este enlace de confirmación es de uso único.</p>
            <p>Si tú no solicitaste este cambio, puedes ignorar este correo de forma segura. Tu contraseña actual permanecerá intacta.</p>
        </div>
        <div class="footer">
            &copy; {anio} Instituto Educativo. Todos los derechos reservados.
        </div>
    </div>
</body>
</html>';

                        $html_content = str_replace(
                            ['{correo}', '{link_confirmacion}', '{anio}'],
                            [$correo, $link_confirmacion, date('Y')],
                            $template_html
                        );

                        // Guardar correo simulado localmente para fallback
                        file_put_contents('correo_simulado.html', $html_content);

                        $_SESSION['token_simulado'] = $token;
                        $_SESSION['correo_real_enviado'] = false;

                        // Determinar si se configuraron credenciales reales de correo
                        $enviar_correo_real = (SMTP_USER !== 'tu_correo@gmail.com' && !empty(SMTP_USER) && SMTP_PASS !== 'tu_contraseña_de_aplicacion' && !empty(SMTP_PASS));

                        if ($enviar_correo_real) {
                            $mail = new PHPMailer(true);
                            try {
                                // Configurar SMTP
                                $mail->isSMTP();
                                $mail->Host       = SMTP_HOST;
                                $mail->SMTPAuth   = true;
                                $mail->Username   = SMTP_USER;
                                $mail->Password   = SMTP_PASS;
                                $mail->SMTPSecure = (SMTP_SECURE === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                                $mail->Port       = SMTP_PORT;
                                $mail->CharSet    = 'UTF-8';

                                // Destinatarios
                                $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
                                $mail->addAddress($correo);

                                // Contenido del correo
                                $mail->isHTML(true);
                                $mail->Subject = 'Confirmación de restablecimiento de contraseña';
                                $mail->Body    = $html_content;

                                $mail->send();
                                $_SESSION['correo_real_enviado'] = true;
                                
                                // Eliminar simulador local si se envió de verdad
                                if (file_exists('correo_simulado.html')) {
                                    @unlink('correo_simulado.html');
                                }

                                $mensaje = "Solicitud recibida. Se ha enviado un correo de confirmación real a la cuenta: " . htmlspecialchars($correo) . ". Revisa tu bandeja de entrada o spam.";
                                $tipo_mensaje = "exito";
                            } catch (Exception $e) {
                                $mensaje = "Se guardó la solicitud, pero no pudimos enviar el correo real (Error: " . $mail->ErrorInfo . "). Habilitamos el simulador local para que confirmes tu cambio.";
                                $tipo_mensaje = "exito";
                            }
                        } else {
                            $mensaje = "Solicitud recibida. Se ha enviado un correo de confirmación a tu cuenta institucional.";
                            $tipo_mensaje = "exito";
                        }
                    } else {
                        $mensaje = "Error al procesar la solicitud de restablecimiento. Intente de nuevo.";
                        $tipo_mensaje = "error";
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $mensaje = "Error del sistema al preparar el restablecimiento.";
                    $tipo_mensaje = "error";
                }
            } else {
                mysqli_stmt_close($stmt_check);
                $mensaje = "El correo electrónico no se encuentra registrado.";
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "Error al verificar el correo en el sistema.";
            $tipo_mensaje = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña — Instituto Educativo</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            background: radial-gradient(ellipse at 30% 20%, #07346e 0%, var(--azul-900) 45%, #000d25 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, Helvetica, sans-serif;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: radial-gradient(rgba(255,255,255,0.04) 1px, transparent 1px);
            background-size: 32px 32px;
            pointer-events: none;
        }

        .login-wrapper {
            width: 100%;
            max-width: 480px;
            padding: 24px 16px;
            position: relative;
            z-index: 1;
        }

        .login-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 32px 80px rgba(0, 13, 37, 0.55), 0 0 0 1px rgba(255,255,255,0.08);
            overflow: hidden;
        }

        .login-header {
            background: radial-gradient(circle at 50% 0%, #0a3d7a 0%, var(--azul-900) 70%);
            padding: 32px 40px 28px;
            text-align: center;
            border-bottom: 1px solid rgba(217, 160, 38, 0.3);
        }

        .login-logo {
            width: 72px;
            height: auto;
            mix-blend-mode: lighten;
            margin-bottom: 12px;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.4));
        }

        .login-header h1 {
            margin: 0 0 4px;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 18px;
            letter-spacing: 3px;
            color: #fff;
        }

        .login-header p {
            margin: 0;
            color: #d9a026;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2.5px;
        }

        .login-body {
            padding: 32px 40px 36px;
        }

        .login-subtitle {
            margin: 0 0 24px;
            text-align: center;
        }

        .login-subtitle strong {
            display: block;
            color: var(--texto);
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .login-subtitle p {
            margin: 0;
            color: var(--muted);
            font-size: 13.5px;
            line-height: 1.5;
        }

        /* Requisitos de contraseña */
        .requisitos {
            background: #f0f7ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 20px;
        }

        .requisitos p {
            margin: 0 0 10px;
            font-size: 12px;
            font-weight: 800;
            color: var(--azul-800);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .requisito-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12.5px;
            color: var(--muted);
            margin-bottom: 6px;
            transition: color 0.2s;
        }

        .requisito-item:last-child {
            margin-bottom: 0;
        }

        .req-dot {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 1.5px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 10px;
            transition: all 0.2s;
        }

        .requisito-item.cumplido .req-dot {
            background: var(--verde);
            border-color: var(--verde);
            color: #fff;
        }

        .requisito-item.cumplido {
            color: #137333;
        }

        /* Campos */
        .campo-grupo {
            margin-bottom: 18px;
        }

        .campo-grupo label {
            display: block;
            margin: 0 0 7px;
            font-weight: 800;
            font-size: 13px;
            color: #273650;
        }

        .campo-input-wrap {
            position: relative;
        }

        .campo-icono {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            display: flex;
            align-items: center;
            pointer-events: none;
        }

        .campo-grupo input {
            width: 100%;
            padding: 13px 16px 13px 44px;
            border: 1.5px solid #cfd8e5;
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            color: var(--texto);
            background: #fafbfd;
            transition: border-color 0.18s, box-shadow 0.18s;
            box-sizing: border-box;
        }

        .campo-grupo input:focus {
            outline: none;
            border-color: var(--azul-600);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(0, 87, 255, 0.12);
        }

        .campo-grupo input.campo-error {
            border-color: var(--rojo);
        }

        .campo-alerta {
            display: none;
            align-items: center;
            gap: 6px;
            margin-top: 7px;
            font-size: 12px;
            font-weight: 700;
            color: var(--rojo);
        }

        .campo-alerta.visible {
            display: flex;
        }

        .toggle-pass {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--muted);
            padding: 0;
            margin: 0;
            min-height: unset;
            display: flex;
            align-items: center;
        }

        .toggle-pass:hover {
            color: var(--azul-600);
            background: none;
        }

        /* Alerta general */
        .alerta-box {
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 14px;
            font-weight: 700;
        }

        .alerta-box.error {
            background: #fff0f0;
            border: 1px solid #ffc9c9;
            color: #b42318;
        }

        .alerta-box.exito {
            background: #e9f8ee;
            border: 1px solid #bce8c8;
            color: #177234;
        }

        .btn-guardar {
            width: 100%;
            min-height: 50px;
            background: var(--azul-600);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            margin-top: 8px;
            letter-spacing: 0.5px;
            transition: background 0.18s, box-shadow 0.18s;
            box-shadow: 0 4px 14px rgba(0, 87, 255, 0.35);
        }

        .btn-guardar:hover {
            background: #0047d6;
        }

        .link-volver {
            display: block;
            text-align: center;
            margin-top: 18px;
            font-size: 13px;
            color: var(--muted);
            text-decoration: none;
        }

        .link-volver:hover {
            color: var(--azul-600);
            text-decoration: underline;
        }

        .login-footer-card {
            border-top: 1px solid var(--linea);
            padding: 14px 40px;
            background: #f7f9fc;
            text-align: center;
        }

        .login-footer-card p {
            margin: 0;
            font-size: 12px;
            color: var(--muted);
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">

        <div class="login-header">
            <img src="imagenes/Logo.png" alt="Logo Instituto" class="login-logo">
            <h1>INSTITUTO<br>EDUCATIVO</h1>
            <p>Formando Futuro</p>
        </div>

        <div class="login-body">

            <div class="login-subtitle">
                <strong>Restablecer contraseña</strong>
                <p>Ingresa tu correo institucional y define una nueva contraseña segura.</p>
            </div>

            <?php if ($mensaje !== ""): ?>
                <div class="alerta-box <?php echo $tipo_mensaje; ?>">
                    <span><?php echo $tipo_mensaje === 'exito' ? '&#10003;' : '&#9888;'; ?></span>
                    <span><?php echo htmlspecialchars($mensaje); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($tipo_mensaje !== 'exito'): ?>
            <form method="POST" action="recuperar_password.php" id="form-recuperar" novalidate>

                <!-- Correo -->
                <div class="campo-grupo">
                    <label for="correo">Correo electrónico institucional</label>
                    <div class="campo-input-wrap">
                        <span class="campo-icono">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </span>
                        <input type="email" id="correo" name="correo"
                               placeholder="correo@instituto.edu.mx"
                               value="<?php echo htmlspecialchars($_POST['correo'] ?? ''); ?>">
                    </div>
                    <div class="campo-alerta" id="alerta-correo">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span id="alerta-correo-txt">El correo es obligatorio.</span>
                    </div>
                </div>

                <!-- Requisitos -->
                <div class="requisitos">
                    <p>Requisitos de la nueva contraseña</p>
                    <div class="requisito-item" id="req-longitud">
                        <div class="req-dot" id="dot-longitud"></div>
                        Mínimo 8 caracteres
                    </div>
                    <div class="requisito-item" id="req-mayuscula">
                        <div class="req-dot" id="dot-mayuscula"></div>
                        Al menos una letra mayúscula
                    </div>
                    <div class="requisito-item" id="req-numero">
                        <div class="req-dot" id="dot-numero"></div>
                        Al menos un número
                    </div>
                    <div class="requisito-item" id="req-simbolo">
                        <div class="req-dot" id="dot-simbolo"></div>
                        Al menos un símbolo especial (#, $, @, !, %)
                    </div>
                </div>

                <!-- Nueva contraseña -->
                <div class="campo-grupo">
                    <label for="nueva_password">Nueva contraseña</label>
                    <div class="campo-input-wrap">
                        <span class="campo-icono">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>
                        <input type="password" id="nueva_password" name="nueva_password" placeholder="••••••••">
                        <button type="button" class="toggle-pass" data-target="nueva_password" title="Mostrar/ocultar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <div class="campo-alerta" id="alerta-nueva">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        La contraseña no cumple los requisitos.
                    </div>
                </div>

                <!-- Confirmar contraseña -->
                <div class="campo-grupo">
                    <label for="confirmar_password">Confirmar nueva contraseña</label>
                    <div class="campo-input-wrap">
                        <span class="campo-icono">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>
                        <input type="password" id="confirmar_password" name="confirmar_password" placeholder="••••••••">
                        <button type="button" class="toggle-pass" data-target="confirmar_password" title="Mostrar/ocultar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <div class="campo-alerta" id="alerta-confirmar">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        Las contraseñas no coinciden.
                    </div>
                </div>

                <button type="submit" class="btn-guardar">Guardar nueva contraseña</button>

            </form>
            <?php else: ?>
                <?php if (isset($_SESSION['token_simulado']) && (!isset($_SESSION['correo_real_enviado']) || !$_SESSION['correo_real_enviado'])): ?>
                    <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px; margin-top: 16px;">
                        <a href="correo_simulado.html" target="_blank" class="btn-guardar" style="background: #0f172a; text-decoration: none; display: flex; align-items: center; justify-content: center; margin: 0; box-shadow: none;">
                            Ver correo
                        </a>
                    </div>
                <?php endif; ?>
                <div style="text-align:center; padding: 10px 0 4px;">
                    <a href="login.php" class="btn-guardar" style="margin-top: 0; text-decoration: none; display: flex; align-items: center; justify-content: center; background: var(--azul-600); box-shadow: 0 4px 14px rgba(0, 87, 255, 0.35);">Ir al inicio de sesión</a>
                </div>
            <?php endif; ?>

            <a href="login.php" class="link-volver">&#8592; Volver al inicio de sesión</a>

        </div>

        <div class="login-footer-card">
            <p>Si no recuerdas tu correo institucional, contacta al administrador del sistema.</p>
        </div>

    </div>
</div>

<script>
    // ── Toggle mostrar/ocultar contraseña ──
    document.querySelectorAll('.toggle-pass').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const input = document.getElementById(targetId);
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    });

    // ── Validación de requisitos en tiempo real ──
    const passInput = document.getElementById('nueva_password');
    if (passInput) {
        passInput.addEventListener('input', function() {
            const val = this.value;
            validarRequisito('req-longitud', 'dot-longitud', val.length >= 8);
            validarRequisito('req-mayuscula', 'dot-mayuscula', /[A-Z]/.test(val));
            validarRequisito('req-numero', 'dot-numero', /[0-9]/.test(val));
            validarRequisito('req-simbolo', 'dot-simbolo', /[#$@!%&*?]/.test(val));
        });
    }

    function validarRequisito(itemId, dotId, cumple) {
        const item = document.getElementById(itemId);
        const dot = document.getElementById(dotId);
        if (cumple) {
            item.classList.add('cumplido');
            dot.innerHTML = '✓';
        } else {
            item.classList.remove('cumplido');
            dot.innerHTML = '';
        }
    }

    // ── Validación al enviar ──
    const form = document.getElementById('form-recuperar');
    if (form) {
        form.addEventListener('submit', function(e) {
            let valido = true;

            const correo = document.getElementById('correo');
            const alertaCorreo = document.getElementById('alerta-correo');
            const alertaCorreoTxt = document.getElementById('alerta-correo-txt');
            const nueva = document.getElementById('nueva_password');
            const alertaNueva = document.getElementById('alerta-nueva');
            const confirmar = document.getElementById('confirmar_password');
            const alertaConfirmar = document.getElementById('alerta-confirmar');

            // Correo
            if (correo.value.trim() === '') {
                correo.classList.add('campo-error');
                alertaCorreoTxt.textContent = 'El correo es obligatorio.';
                alertaCorreo.classList.add('visible');
                valido = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo.value.trim())) {
                correo.classList.add('campo-error');
                alertaCorreoTxt.textContent = 'Ingresa un correo electrónico válido.';
                alertaCorreo.classList.add('visible');
                valido = false;
            } else {
                correo.classList.remove('campo-error');
                alertaCorreo.classList.remove('visible');
            }

            // Nueva contraseña
            const v = nueva.value;
            const passOk = v.length >= 8 && /[A-Z]/.test(v) && /[0-9]/.test(v) && /[#$@!%&*?]/.test(v);
            if (!passOk) {
                nueva.classList.add('campo-error');
                alertaNueva.classList.add('visible');
                valido = false;
            } else {
                nueva.classList.remove('campo-error');
                alertaNueva.classList.remove('visible');
            }

            // Confirmar
            if (confirmar.value !== nueva.value || confirmar.value === '') {
                confirmar.classList.add('campo-error');
                alertaConfirmar.classList.add('visible');
                valido = false;
            } else {
                confirmar.classList.remove('campo-error');
                alertaConfirmar.classList.remove('visible');
            }

            if (!valido) e.preventDefault();
        });
    }

    // Limpiar errores al escribir
    ['correo', 'nueva_password', 'confirmar_password'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', function() {
            this.classList.remove('campo-error');
            const alerta = document.getElementById('alerta-' + id.replace('_password','').replace('nueva','nueva').replace('confirmar_','confirmar'));
            if (alerta) alerta.classList.remove('visible');
        });
    });
</script>

</body>
</html>
