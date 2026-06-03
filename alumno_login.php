<?php
// Vista de Ingreso para Alumnos
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso a Examen - Instituto Educativo</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        body {
            background-color: #f8fafc;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #1e293b;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
            box-sizing: border-box;
        }

        .logo-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-header img {
            width: 320px;
            height: auto;
            margin-bottom: 10px;
        }

        .logo-header h1 {
            font-size: 20px;
            margin: 0;
            color: var(--azul-900);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .logo-header span {
            font-size: 12px;
            color: #d97706;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .login-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            padding: 40px 30px;
            text-align: center;
        }

        .login-card h2 {
            font-size: 24px;
            margin: 0 0 10px 0;
            color: var(--azul-900);
        }

        .login-card p {
            color: var(--muted);
            font-size: 14px;
            margin: 0 0 30px 0;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
            text-align: left;
        }

        .input-group svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            width: 18px;
            height: 18px;
        }

        .input-group input {
            width: 100%;
            padding: 14px 14px 14px 44px;
            border: 1px solid var(--linea);
            border-radius: 8px;
            font-size: 14px;
            color: var(--texto);
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.2s;
        }

        .input-group input:focus {
            border-color: var(--azul-600);
        }

        .input-group input::placeholder {
            color: #94a3b8;
        }

        .btn-submit {
            width: 100%;
            background: var(--azul-900);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 14px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background 0.2s;
            margin-bottom: 24px;
        }

<<<<<<< HEAD
         .btn-submit:hover {
            background: #0f172a;
        }

        .btn-secondary-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: var(--muted);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 600;
            margin-top: 8px;
            margin-bottom: 24px;
            transition: color 0.2s;
        }

        .btn-secondary-link:hover {
            color: var(--azul-900);
        }

=======
        .btn-submit:hover {
            background: #0f172a;
        }

>>>>>>> b0ce2526d8b7fbcd61a207990251e08376002d93
        .divider {
            height: 1px;
            background: var(--linea);
            margin: 0 auto 24px;
            width: 40px;
        }

        .info-box {
            background: #f0fdf4;
            border-radius: 8px;
            padding: 16px;
            display: flex;
            gap: 12px;
            text-align: left;
            align-items: flex-start;
        }

        .info-box svg {
            color: #16a34a;
            flex-shrink: 0;
        }

        .info-box span {
            font-size: 12.5px;
            color: #334155;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-header">
            <img src="imagenes/Logo.png" alt="Logo">
            <h1>Instituto Educativo</h1>
            <span>Formando Futuro</span>
        </div>

        <div class="login-card">
            <h2>Bienvenido</h2>
            <p>Ingresa tus datos para presentar tu examen</p>

            <form action="tomar_examen.php" method="POST">
                <div class="input-group">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <input type="text" name="nombre_estudiante" placeholder="Escribe tu nombre completo" required>
                </div>

                <div class="input-group">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <input type="text" name="codigo_examen" placeholder="Ingresa el código proporcionado por tu profesor" required>
                </div>

                <button type="submit" class="btn-submit">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    Comenzar examen
                </button>
            </form>

<<<<<<< HEAD
            <a href="index.php" class="btn-secondary-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Regresar al inicio
            </a>

=======
>>>>>>> b0ce2526d8b7fbcd61a207990251e08376002d93
            <div class="divider"></div>

            <div class="info-box">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    <polyline points="9 12 11 14 15 10"></polyline>
                </svg>
                <span>Asegúrate de ingresar correctamente tu nombre y el código del examen para poder continuar.</span>
            </div>
        </div>
    </div>
</body>
</html>
