<?php
session_start();
include 'config/conexion.php';

$exito = false;
$mensaje = "";

if (!isset($_GET['token']) || empty($_GET['token'])) {
    $mensaje = "El enlace de confirmación no contiene un token válido.";
} else {
    $token = trim($_GET['token']);

    // Buscar el token en la base de datos que no haya expirado y obtener los segundos transcurridos según el servidor de BD
    $stmt = mysqli_prepare($conexion, "SELECT *, (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(fecha_solicitud)) AS segundos_transcurridos FROM restablecimientos_password WHERE token = ? AND expirado = 0");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        $solicitud = mysqli_fetch_assoc($resultado);
        mysqli_stmt_close($stmt);

        if ($solicitud) {
            // Verificar expiración temporal (ej. 1 hora = 3600 segundos)
            $diferencia = intval($solicitud['segundos_transcurridos']);

            if ($diferencia > 3600) {
                // Token expirado por tiempo
                $mensaje = "El enlace de confirmación ha caducado por límite de tiempo (1 hora). Por favor solicita uno nuevo.";
                // Marcar como expirado en la BD
                $stmt_exp = mysqli_prepare($conexion, "UPDATE restablecimientos_password SET expirado = 1 WHERE token = ?");
                if ($stmt_exp) {
                    mysqli_stmt_bind_param($stmt_exp, "s", $token);
                    mysqli_stmt_execute($stmt_exp);
                    mysqli_stmt_close($stmt_exp);
                }
            } else {
                // Proceder a actualizar la contraseña en la tabla profesores
                mysqli_begin_transaction($conexion);

                $correo = $solicitud['correo'];
                $hash = $solicitud['password_hash'];

                $stmt_update = mysqli_prepare($conexion, "UPDATE profesores SET password = ? WHERE correo = ?");
                $stmt_expire = mysqli_prepare($conexion, "UPDATE restablecimientos_password SET expirado = 1 WHERE token = ?");

                if ($stmt_update && $stmt_expire) {
                    mysqli_stmt_bind_param($stmt_update, "ss", $hash, $correo);
                    mysqli_stmt_bind_param($stmt_expire, "s", $token);

                    $ok1 = mysqli_stmt_execute($stmt_update);
                    $ok2 = mysqli_stmt_execute($stmt_expire);

                    if ($ok1 && $ok2) {
                        mysqli_commit($conexion);
                        $exito = true;
                        $mensaje = "¡Contraseña actualizada con éxito! Se ha verificado tu identidad institucional.";

                        // Limpiar simulación local
                        if (file_exists('correo_simulado.html')) {
                            @unlink('correo_simulado.html');
                        }
                        unset($_SESSION['token_simulado']);
                    } else {
                        mysqli_rollback($conexion);
                        $mensaje = "Ocurrió un error en el servidor al intentar actualizar tu contraseña.";
                    }
                    
                    mysqli_stmt_close($stmt_update);
                    mysqli_stmt_close($stmt_expire);
                } else {
                    mysqli_rollback($conexion);
                    $mensaje = "Error al preparar la actualización de seguridad.";
                }
            }
        } else {
            $mensaje = "El enlace de confirmación no es válido o ya ha sido utilizado.";
        }
    } else {
        $mensaje = "Error del sistema al validar tu solicitud.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Restablecimiento — Instituto Educativo</title>
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
            color: var(--texto);
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: radial-gradient(rgba(255,255,255,0.04) 1px, transparent 1px);
            background-size: 32px 32px;
            pointer-events: none;
        }

        .confirm-wrapper {
            width: 100%;
            max-width: 500px;
            padding: 24px 16px;
            position: relative;
            z-index: 1;
        }

        .confirm-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 32px 80px rgba(0, 13, 37, 0.55), 0 0 0 1px rgba(255,255,255,0.08);
            overflow: hidden;
            text-align: center;
        }

        .confirm-header {
            background: radial-gradient(circle at 50% 0%, #0a3d7a 0%, var(--azul-900) 70%);
            padding: 32px 40px 28px;
            border-bottom: 1px solid rgba(217, 160, 38, 0.3);
            color: #fff;
        }

        .confirm-logo {
            width: 72px;
            height: auto;
            mix-blend-mode: lighten;
            margin-bottom: 12px;
        }

        .confirm-header h1 {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 18px;
            letter-spacing: 3px;
        }

        .confirm-body {
            padding: 40px 40px 36px;
        }

        .icon-circle {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }

        .icon-success {
            background: #dcfce7;
            color: #16a34a;
        }

        .icon-error {
            background: #fee2e2;
            color: #ef4444;
        }

        .confirm-title {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 12px;
            color: var(--texto);
        }

        .confirm-text {
            font-size: 14.5px;
            color: var(--muted);
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 50px;
            background: var(--azul-600);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 800;
            transition: background 0.18s;
            box-shadow: 0 4px 14px rgba(0, 87, 255, 0.35);
        }

        .btn-action:hover {
            background: #0047d6;
        }

        .btn-sec {
            background: #64748b;
            box-shadow: none;
            margin-top: 12px;
        }

        .btn-sec:hover {
            background: #475569;
        }

        .redirect-txt {
            font-size: 12px;
            color: var(--muted);
            margin-top: 16px;
        }
    </style>
</head>
<body>

<div class="confirm-wrapper">
    <div class="confirm-card">
        <div class="confirm-header">
            <img src="imagenes/Logo.png" alt="Logo" class="confirm-logo">
            <h1>INSTITUTO EDUCATIVO</h1>
        </div>

        <div class="confirm-body">
            <?php if ($exito): ?>
                <div class="icon-circle icon-success">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <div class="confirm-title">¡Confirmación Exitosa!</div>
                <div class="confirm-text">
                    <?php echo htmlspecialchars($mensaje); ?><br>
                    Tu nueva contraseña se ha guardado de forma segura y ya está lista para su uso.
                </div>
                <a href="login.php" class="btn-action">Iniciar sesión ahora</a>
                <div class="redirect-txt" id="timer-msg">Redirigiendo automáticamente en <span id="countdown">5</span> segundos...</div>
                
                <script>
                    let count = 5;
                    const interval = setInterval(() => {
                        count--;
                        document.getElementById('countdown').innerText = count;
                        if (count <= 0) {
                            clearInterval(interval);
                            window.location.href = 'login.php';
                        }
                    }, 1000);
                </script>

            <?php else: ?>
                <div class="icon-circle icon-error">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </div>
                <div class="confirm-title">Error de Confirmación</div>
                <div class="confirm-text"><?php echo htmlspecialchars($mensaje); ?></div>
                <a href="recuperar_password.php" class="btn-action">Solicitar nuevo cambio</a>
                <a href="login.php" class="btn-action btn-sec">Volver al inicio</a>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
