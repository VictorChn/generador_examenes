<?php
session_start();

// Si ya hay sesión activa de administrador, redirigir al dashboard de admin
if (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit;
}

$error = "";
$mensaje_exito = "";

if (isset($_GET['msg']) && isset($_GET['tipo']) && $_GET['tipo'] === 'exito') {
    $mensaje_exito = $_GET['msg'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = trim($_POST['correo'] ?? '');
    $password_admin = $_POST['password'] ?? '';

    if ($correo === "" || $password_admin === "") {
        $error = "Todos los campos son obligatorios.";
    } else {
        include 'config/conexion.php';

        $stmt = mysqli_prepare($conexion, "SELECT * FROM administradores WHERE correo = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $correo);
            mysqli_stmt_execute($stmt);
            $resultado = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($resultado)) {
                // Verificar contraseña cifrada
                if (password_verify($password_admin, $row['password'])) {
                    $_SESSION['admin_id'] = $row['id_admin'];
                    $_SESSION['admin_nombre'] = $row['nombre'];

                    header("Location: admin_dashboard.php");
                    exit;
                } else {
                    $error = "Correo o contraseña incorrectos.";
                }
            } else {
                $error = "Correo o contraseña incorrectos.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = "Ocurrió un error en el servidor. Por favor intenta más tarde.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Administrador — Instituto Educativo</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            background: radial-gradient(ellipse at 30% 20%, #6e071c 0%, #45000e 45%, #250005 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, Helvetica, sans-serif;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: radial-gradient(rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 32px 32px;
            pointer-events: none;
        }

        .login-wrapper {
            width: 100%;
            max-width: 460px;
            padding: 24px 16px;
            position: relative;
            z-index: 1;
        }

        .login-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 32px 80px rgba(37, 0, 5, 0.55), 0 0 0 1px rgba(255,255,255,0.08);
            overflow: hidden;
        }

        .login-header {
            background: radial-gradient(circle at 50% 0%, #7a0a20 0%, #45000e 70%);
            padding: 40px 40px 32px;
            text-align: center;
            border-bottom: 1px solid rgba(217, 160, 38, 0.3);
        }

        .login-logo {
            width: 100px;
            height: auto;
            mix-blend-mode: lighten;
            margin-bottom: 16px;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.4));
        }

        .login-header h1 {
            margin: 0 0 4px;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 20px;
            letter-spacing: 3px;
            color: #fff;
            font-weight: 700;
        }

        .login-header p {
            margin: 0;
            color: #d9a026;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2.5px;
            text-transform: uppercase;
        }

        .login-body {
            padding: 36px 40px 40px;
        }

        .login-subtitle {
            margin: 0 0 28px;
            color: var(--muted);
            font-size: 14px;
            text-align: center;
        }

        .login-subtitle strong {
            display: block;
            color: var(--texto);
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .campo-grupo {
            margin-bottom: 20px;
        }

        .campo-grupo label {
            display: block;
            margin: 0 0 8px;
            font-weight: 800;
            font-size: 13px;
            color: #273650;
            letter-spacing: 0.3px;
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
            font-size: 16px;
            pointer-events: none;
            display: flex;
            align-items: center;
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
            transition: border-color 0.18s, box-shadow 0.18s, background 0.18s;
            box-sizing: border-box;
        }

        .campo-grupo input:focus {
            outline: none;
            border-color: #7a0a20;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(122, 10, 32, 0.12);
        }

        .campo-grupo input.campo-error {
            border-color: var(--rojo);
            background: #fff8f8;
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

        .alerta-error {
            background: #fff0f0;
            border: 1px solid #ffc9c9;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 700;
            color: #b42318;
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
            font-size: 16px;
            display: flex;
            align-items: center;
        }

        .toggle-pass:hover {
            color: #7a0a20;
            background: none;
        }

        .btn-ingresar {
            width: 100%;
            min-height: 50px;
            background: #7a0a20;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            margin-top: 8px;
            letter-spacing: 0.5px;
            transition: background 0.18s, transform 0.1s, box-shadow 0.18s;
            box-shadow: 0 4px 14px rgba(122, 10, 32, 0.35);
        }

        .btn-ingresar:hover {
            background: #5c0818;
            box-shadow: 0 6px 20px rgba(122, 10, 32, 0.45);
        }

        .btn-ingresar:active {
            transform: scale(0.985);
        }

        .login-footer-card {
            border-top: 1px solid var(--linea);
            padding: 16px 40px;
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
            <img src="imagenes/Logo.png" alt="Logo Instituto Educativo" class="login-logo">
            <h1>INSTITUTO<br>EDUCATIVO</h1>
            <p>Formando Futuro</p>
        </div>

        <div class="login-body">

            <div class="login-subtitle">
                <strong>Panel Administrativo</strong>
                Ingresa con tus credenciales de administrador
            </div>

            <?php if ($error !== ""): ?>
                <div class="alerta-error">
                    <span class="alerta-icono">&#9888;</span>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($mensaje_exito !== ""): ?>
                <div class="alerta-error" style="background: #e9f8ee; border: 1px solid #bce8c8; color: #177234;">
                    <span class="alerta-icono" style="color: #177234;">&#10003;</span>
                    <?php echo htmlspecialchars($mensaje_exito); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login_admin.php" id="form-login" novalidate>

                <!-- Correo -->
                <div class="campo-grupo">
                    <label for="correo">Correo electrónico administrativo</label>
                    <div class="campo-input-wrap">
                        <span class="campo-icono">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </span>
                        <input
                            type="email"
                            id="correo"
                            name="correo"
                            placeholder="admin@instituto.edu.mx"
                            value="<?php echo htmlspecialchars($_POST['correo'] ?? ''); ?>"
                            autocomplete="email"
                        >
                    </div>
                    <div class="campo-alerta" id="alerta-correo">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span id="alerta-correo-texto">El correo es obligatorio.</span>
                    </div>
                </div>

                <!-- Contraseña -->
                <div class="campo-grupo">
                    <label for="password">Contraseña</label>
                    <div class="campo-input-wrap">
                        <span class="campo-icono">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="••••••••"
                            autocomplete="current-password"
                        >
                        <button type="button" class="toggle-pass" id="toggle-pass" title="Mostrar/ocultar contraseña">
                            <svg id="icono-ojo" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <div class="campo-alerta" id="alerta-password">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        La contraseña es obligatoria.
                    </div>
                </div>

                <button type="submit" class="btn-ingresar" id="btn-ingresar">
                    Ingresar al Panel
                </button>

            </form>

        </div>

        <div class="login-footer-card">
            <p>Acceso restringido para administradores. Si eres docente, ingresa por el acceso regular.</p>
        </div>

    </div>
</div>

<script>
    const toggleBtn = document.getElementById('toggle-pass');
    const passInput = document.getElementById('password');
    const iconoOjo = document.getElementById('icono-ojo');

    const ojoAbierto = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
    const ojoCerrado = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>`;

    toggleBtn.addEventListener('click', () => {
        const esPassword = passInput.type === 'password';
        passInput.type = esPassword ? 'text' : 'password';
        iconoOjo.innerHTML = esPassword ? ojoCerrado : ojoAbierto;
    });

    document.getElementById('form-login').addEventListener('submit', function(e) {
        let valido = true;

        const correo = document.getElementById('correo');
        const alertaCorreo = document.getElementById('alerta-correo');
        const alertaCorreoTexto = document.getElementById('alerta-correo-texto');

        const password = document.getElementById('password');
        const alertaPassword = document.getElementById('alerta-password');

        if (correo.value.trim() === '') {
            correo.classList.add('campo-error');
            alertaCorreoTexto.textContent = 'El correo es obligatorio.';
            alertaCorreo.classList.add('visible');
            valido = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo.value.trim())) {
            correo.classList.add('campo-error');
            alertaCorreoTexto.textContent = 'Ingresa un correo electrónico válido.';
            alertaCorreo.classList.add('visible');
            valido = false;
        } else {
            correo.classList.remove('campo-error');
            alertaCorreo.classList.remove('visible');
        }

        if (password.value === '') {
            password.classList.add('campo-error');
            alertaPassword.classList.add('visible');
            valido = false;
        } else {
            password.classList.remove('campo-error');
            alertaPassword.classList.remove('visible');
        }

        if (!valido) {
            e.preventDefault();
        }
    });

    document.getElementById('correo').addEventListener('input', function() {
        this.classList.remove('campo-error');
        document.getElementById('alerta-correo').classList.remove('visible');
    });

    document.getElementById('password').addEventListener('input', function() {
        this.classList.remove('campo-error');
        document.getElementById('alerta-password').classList.remove('visible');
    });
</script>

</body>
</html>
