<?php
// Página principal de inicio (Landing)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma de Evaluación - Instituto Educativo</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, var(--azul-900) 0%, #1e3a8a 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: #fff;
        }

        .header-top {
            background: rgba(0, 0, 0, 0.2);
            padding: 16px;
            text-align: center;
            font-weight: bold;
            letter-spacing: 2px;
            font-size: 14px;
            text-transform: uppercase;
        }

        .main-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .logo-container {
            width: 260px;
            height: 260px;
            background: rgba(255, 255, 255, 0.04);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 32px;
            box-shadow: 0 0 60px rgba(255, 255, 255, 0.02);
        }

        .logo-container img {
            width: 450px;
            height: auto;
            mix-blend-mode: lighten;
        }

        h1 {
            font-size: 42px;
            margin: 0 0 10px 0;
            font-weight: 800;
            text-align: center;
        }

        .subtitle {
            font-size: 16px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 10px;
        }

        .institution-name {
            font-size: 18px;
            margin-bottom: 60px;
        }

        .cards-grid {
            display: flex;
            gap: 30px;
            max-width: 900px;
            width: 100%;
            justify-content: center;
            flex-wrap: wrap;
        }

        .role-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 40px 30px;
            width: 320px;
            text-align: center;
            text-decoration: none;
            color: #fff;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .role-card:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-5px);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .role-icon {
            width: 64px;
            height: 64px;
            margin-bottom: 20px;
            color: #fff;
        }

        .role-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 12px;
        }

        .role-desc {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.5;
        }

        .footer {
            text-align: center;
            padding: 20px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header-top">
        Plataforma de Evaluación Institucional
    </div>

    <div class="main-container">
        <div class="logo-container">
            <img src="imagenes/Logo.png" alt="Logo Instituto Educativo">
        </div>

        <h1>Sistema de Evaluación</h1>
        <div class="marca-texto">
                    <strong>INSTITUTO<br>EDUCATIVO</strong>
                    <span>FORMANDO FUTURO</span>
                </div>
                <br>

        <div class="cards-grid">
            <a href="alumno_login.php" class="role-card">
                <svg class="role-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <div class="role-title">Alumno</div>
                <div class="role-desc">Módulo de resolución de exámenes y evaluación para estudiantes.</div>
            </a>

            <a href="dashboard.php" class="role-card">
                <svg class="role-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                    <line x1="8" y1="21" x2="16" y2="21"></line>
                    <line x1="12" y1="17" x2="12" y2="21"></line>
                </svg>
                <div class="role-title">Docente</div>
                <div class="role-desc">Módulo de administración de banco de preguntas y generación de exámenes.</div>
            </a>
        </div>
    </div>

    <div class="footer">
        &copy; <?php echo date('Y'); ?> Instituto Educativo. Todos los derechos reservados.
    </div>
</body>
</html>
