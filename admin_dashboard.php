<?php
session_start();

// Protección de sesión para administrador
if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit;
}

include 'config/conexion.php';

// Cargar configuración de correo con valores por defecto
if (file_exists('config/email.php')) {
    include_once 'config/email.php';
}
if (!defined('SMTP_HOST')) define('SMTP_HOST', 'smtp.gmail.com');
if (!defined('SMTP_PORT')) define('SMTP_PORT', 465);
if (!defined('SMTP_SECURE')) define('SMTP_SECURE', 'ssl');
if (!defined('SMTP_USER')) define('SMTP_USER', 'tu_correo@gmail.com');
if (!defined('SMTP_PASS')) define('SMTP_PASS', 'tu_contraseña_de_aplicacion');
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', 'Instituto Educativo');

$mensaje = "";
$tipo_mensaje = "";
$seccion = $_GET['seccion'] ?? 'profesores';

// ── Acción: guardar configuración de correo (POST) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_form']) && $_POST['accion_form'] === 'configurar_email') {
    $host = trim($_POST['smtp_host'] ?? 'smtp.gmail.com');
    $port = (int)($_POST['smtp_port'] ?? 465);
    $secure = trim($_POST['smtp_secure'] ?? 'ssl');
    $user = trim($_POST['smtp_user'] ?? '');
    $pass = trim($_POST['smtp_pass'] ?? '');
    $from_name = trim($_POST['smtp_from_name'] ?? 'Instituto Educativo');

    if ($user === '' || $pass === '') {
        $mensaje = "El correo del remitente y la contraseña de aplicación son obligatorios.";
        $tipo_mensaje = "error";
    } else {
        $content = "<?php\n"
                 . "// Configuración del servidor de correo saliente (SMTP) autogenerada\n"
                 . "define('SMTP_HOST', '" . addslashes($host) . "');\n"
                 . "define('SMTP_PORT', " . $port . ");\n"
                 . "define('SMTP_SECURE', '" . addslashes($secure) . "');\n"
                 . "define('SMTP_USER', '" . addslashes($user) . "');\n"
                 . "define('SMTP_PASS', '" . addslashes($pass) . "');\n"
                 . "define('SMTP_FROM_NAME', '" . addslashes($from_name) . "');\n"
                 . "?>\n";

        if (file_put_contents('config/email.php', $content) !== false) {
            $mensaje = "Configuración de correo actualizada correctamente.";
            $tipo_mensaje = "exito";
            header("Location: admin_dashboard.php?seccion=correo&msg=" . urlencode($mensaje) . "&tipo=" . $tipo_mensaje);
            exit;
        } else {
            $mensaje = "Error al escribir el archivo de configuración. Verifica los permisos de la carpeta 'config'.";
            $tipo_mensaje = "error";
        }
    }
}

// ── Acción: probar configuración de correo (POST) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_form']) && $_POST['accion_form'] === 'probar_email') {
    $host = trim($_POST['smtp_host'] ?? 'smtp.gmail.com');
    $port = (int)($_POST['smtp_port'] ?? 465);
    $secure = trim($_POST['smtp_secure'] ?? 'ssl');
    $user = trim($_POST['smtp_user'] ?? '');
    $pass = trim($_POST['smtp_pass'] ?? '');
    $from_name = trim($_POST['smtp_from_name'] ?? 'Instituto Educativo');
    $destinatario = trim($_POST['destinatario_prueba'] ?? '');

    if ($user === '' || $pass === '' || $destinatario === '') {
        $mensaje = "Todos los campos (incluyendo el destinatario de prueba) son necesarios para realizar el test.";
        $tipo_mensaje = "error";
    } else {
        require_once 'libs/PHPMailer/src/Exception.php';
        require_once 'libs/PHPMailer/src/PHPMailer.php';
        require_once 'libs/PHPMailer/src/SMTP.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $user;
            $mail->Password   = $pass;
            $mail->SMTPSecure = ($secure === 'ssl') ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $port;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($user, $from_name);
            $mail->addAddress($destinatario);

            $mail->isHTML(true);
            $mail->Subject = 'Prueba de configuracion de correo';
            $mail->Body    = '<h3>¡Conexión Exitosa!</h3><p>Este es un correo de prueba para validar que la configuración SMTP del sistema de exámenes funciona correctamente.</p>';

            $mail->send();
            $mensaje = "¡Correo de prueba enviado con éxito a " . htmlspecialchars($destinatario) . "! La configuración es correcta.";
            $tipo_mensaje = "exito";
        } catch (\Exception $e) {
            $mensaje = "Error al enviar correo de prueba: " . $mail->ErrorInfo;
            $tipo_mensaje = "error";
        }
    }
}

// ── Acción: cambiar estado (suspender / activar) ──
if (isset($_GET['accion']) && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $accion = $_GET['accion'];

    if ($accion === 'suspender') {
        $stmt = mysqli_prepare($conexion, "UPDATE profesores SET estado = 'suspendido' WHERE id_profesor = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $mensaje = "Cuenta suspendida correctamente.";
        $tipo_mensaje = "error";
    } elseif ($accion === 'activar') {
        $stmt = mysqli_prepare($conexion, "UPDATE profesores SET estado = 'activo' WHERE id_profesor = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $mensaje = "Cuenta activada correctamente.";
        $tipo_mensaje = "exito";
    } elseif ($accion === 'eliminar') {
        $stmt = mysqli_prepare($conexion, "DELETE FROM profesores WHERE id_profesor = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $mensaje = "Profesor eliminado del sistema.";
        $tipo_mensaje = "exito";
    }

    header("Location: admin_dashboard.php?msg=" . urlencode($mensaje) . "&tipo=" . $tipo_mensaje);
    exit;
}

// ── Acción: registrar nuevo profesor (POST) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_form']) && $_POST['accion_form'] === 'registrar') {
    $nombre    = trim($_POST['nombre_completo'] ?? '');
    $correo    = trim($_POST['correo'] ?? '');
    $empleado  = trim($_POST['numero_empleado'] ?? '');
    $materia   = trim($_POST['materia'] ?? '');
    $password  = $_POST['password'] ?? '';

    if ($nombre === '' || $correo === '' || $empleado === '' || $materia === '' || $password === '') {
        $mensaje = "Todos los campos son obligatorios.";
        $tipo_mensaje = "error";
    } else {
        // Verificar correo duplicado
        $chk = mysqli_prepare($conexion, "SELECT id_profesor FROM profesores WHERE correo = ? OR numero_empleado = ?");
        mysqli_stmt_bind_param($chk, 'ss', $correo, $empleado);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);

        if (mysqli_stmt_num_rows($chk) > 0) {
            $mensaje = "El correo o número de empleado ya está registrado.";
            $tipo_mensaje = "error";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conexion, "INSERT INTO profesores (nombre_completo, correo, numero_empleado, materia, password) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'sssss', $nombre, $correo, $empleado, $materia, $hash);

            if (mysqli_stmt_execute($stmt)) {
                $mensaje = "Profesor registrado correctamente.";
                $tipo_mensaje = "exito";
            } else {
                $mensaje = "Error al registrar el profesor.";
                $tipo_mensaje = "error";
            }
        }
    }
}

// ── Acción: editar profesor (POST) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_form']) && $_POST['accion_form'] === 'editar') {
    $id       = (int) ($_POST['id_profesor'] ?? 0);
    $nombre   = trim($_POST['nombre_completo'] ?? '');
    $correo   = trim($_POST['correo'] ?? '');
    $empleado = trim($_POST['numero_empleado'] ?? '');
    $materia  = trim($_POST['materia'] ?? '');

    if ($nombre === '' || $correo === '' || $empleado === '' || $materia === '') {
        $mensaje = "Todos los campos son obligatorios.";
        $tipo_mensaje = "error";
    } else {
        $stmt = mysqli_prepare($conexion, "UPDATE profesores SET nombre_completo=?, correo=?, numero_empleado=?, materia=? WHERE id_profesor=?");
        mysqli_stmt_bind_param($stmt, 'ssssi', $nombre, $correo, $empleado, $materia, $id);

        if (mysqli_stmt_execute($stmt)) {
            $mensaje = "Datos del profesor actualizados.";
            $tipo_mensaje = "exito";
        } else {
            $mensaje = "Error al actualizar los datos.";
            $tipo_mensaje = "error";
        }
    }
}

// Recuperar mensaje de redirección
if (isset($_GET['msg'])) {
    $mensaje = htmlspecialchars($_GET['msg']);
    $tipo_mensaje = $_GET['tipo'] ?? 'exito';
}

// ── Filtros y búsqueda ──
$busqueda = trim($_GET['buscar'] ?? '');
$filtro_materia = trim($_GET['materia'] ?? '');
$orden = $_GET['orden'] ?? 'reciente';

$sql_where = "WHERE 1=1";
$params = [];
$types = '';

if ($busqueda !== '') {
    $sql_where .= " AND (nombre_completo LIKE ? OR correo LIKE ?)";
    $like = "%$busqueda%";
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

if ($filtro_materia !== '') {
    $sql_where .= " AND materia = ?";
    $params[] = $filtro_materia;
    $types .= 's';
}

$sql_order = match($orden) {
    'az'      => "ORDER BY nombre_completo ASC",
    'za'      => "ORDER BY nombre_completo DESC",
    'materia' => "ORDER BY materia ASC, nombre_completo ASC",
    default   => "ORDER BY id_profesor DESC"
};

$sql_profesores = "SELECT * FROM profesores $sql_where $sql_order";
$stmt_prof = mysqli_prepare($conexion, $sql_profesores);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt_prof, $types, ...$params);
}
mysqli_stmt_execute($stmt_prof);
$lista_profesores = mysqli_stmt_get_result($stmt_prof);

// Totales para las tarjetas
$resumen = mysqli_fetch_assoc(mysqli_query($conexion, "
    SELECT
        COUNT(*) AS total,
        SUM(estado = 'activo') AS activos,
        SUM(estado = 'suspendido') AS suspendidos,
        COUNT(DISTINCT materia) AS materias
    FROM profesores
"));

// Materias únicas para el filtro
$materias_unicas = mysqli_query($conexion, "SELECT DISTINCT materia FROM profesores ORDER BY materia ASC");

// Profesor para editar (si se solicita)
$profesor_editar = null;
if (isset($_GET['editar'])) {
    $id_editar = (int) $_GET['editar'];
    $stmt_e = mysqli_prepare($conexion, "SELECT * FROM profesores WHERE id_profesor = ?");
    mysqli_stmt_bind_param($stmt_e, 'i', $id_editar);
    mysqli_stmt_execute($stmt_e);
    $resultado_e = mysqli_stmt_get_result($stmt_e);
    $profesor_editar = mysqli_fetch_assoc($resultado_e);
}

$pagina_actual = 'admin_dashboard.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administrador — Instituto Educativo</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        /* ── Layout Admin (sin sidebar del profesor) ── */
        body {
            background: var(--fondo);
        }

        .admin-app {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar admin */
        .admin-sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: 260px;
            background: radial-gradient(circle at 20% 0%, #07346e 0, var(--azul-900) 48%, #000d25 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            padding: 28px 16px 22px;
            box-shadow: 8px 0 28px rgba(0, 20, 60, 0.18);
        }

        .admin-marca {
            text-align: center;
            padding: 0 0 28px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 24px;
        }

        .admin-marca img {
            width: 70px;
            height: auto;
            mix-blend-mode: lighten;
            margin-bottom: 10px;
        }

        .admin-marca strong {
            display: block;
            font-family: Georgia, serif;
            font-size: 15px;
            letter-spacing: 2px;
            line-height: 1.2;
        }

        .admin-marca span {
            display: block;
            color: #d9a026;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 2px;
            margin-top: 6px;
        }

        .admin-badge {
            background: rgba(217,160,38,0.18);
            border: 1px solid rgba(217,160,38,0.4);
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 24px;
            text-align: center;
            font-size: 11px;
            font-weight: 700;
            color: #d9a026;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .admin-nav {
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
        }

        .admin-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 8px;
            color: #eef5ff;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            transition: background 0.15s;
        }

        .admin-nav a:hover,
        .admin-nav a.activo {
            background: var(--azul-600);
        }

        .admin-nav .nav-icono {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.1);
            flex-shrink: 0;
        }

        .admin-perfil {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 16px;
            flex-shrink: 0;
        }

        .admin-perfil strong,
        .admin-perfil span {
            display: block;
            font-size: 13px;
        }

        .admin-perfil span {
            color: #d9a026;
            font-size: 11px;
            font-weight: 700;
        }

        /* Contenido principal */
        .admin-contenido {
            flex: 1;
            margin-left: 260px;
            padding: 36px 32px;
        }

        /* Barra de herramientas */
        .barra-herramientas {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 24px;
        }

        .barra-busqueda {
            flex: 1;
            min-width: 220px;
            position: relative;
        }

        .barra-busqueda svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            pointer-events: none;
        }

        .barra-busqueda input {
            width: 100%;
            padding: 11px 16px 11px 42px;
            border: 1.5px solid var(--linea);
            border-radius: 8px;
            font-size: 14px;
            background: #fff;
            box-sizing: border-box;
            margin: 0;
        }

        .barra-busqueda input:focus {
            border-color: var(--azul-600);
            box-shadow: 0 0 0 3px rgba(0,87,255,0.1);
            outline: none;
        }

        .filtro-select {
            padding: 11px 14px;
            border: 1.5px solid var(--linea);
            border-radius: 8px;
            font-size: 14px;
            background: #fff;
            color: var(--texto);
            font-family: inherit;
            cursor: pointer;
            margin: 0;
            width: auto;
        }

        /* Modal overlay */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(7, 21, 54, 0.55);
            z-index: 100;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay.abierto {
            display: flex;
        }

        .modal {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 24px 60px rgba(0,13,37,0.3);
            width: 100%;
            max-width: 540px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 20px 24px 16px;
            border-bottom: 1px solid var(--linea);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 18px;
            color: var(--texto);
        }

        .modal-cerrar {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--muted);
            padding: 6px;
            margin: 0;
            min-height: unset;
            border-radius: 6px;
            display: flex;
            align-items: center;
        }

        .modal-cerrar:hover {
            background: var(--fondo);
            color: var(--texto);
        }

        .modal-body {
            padding: 24px;
        }

        .modal-body label {
            margin-top: 0;
        }

        .modal-body .campo-grupo {
            margin-bottom: 16px;
        }

        .modal-body .campo-grupo label {
            display: block;
            margin-bottom: 7px;
            font-weight: 800;
            font-size: 13px;
            color: #273650;
        }

        .modal-body .campo-grupo input,
        .modal-body .campo-grupo select {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid #cfd8e5;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            margin: 0;
            box-sizing: border-box;
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--linea);
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .modal-footer .boton,
        .modal-footer button {
            margin: 0;
            min-height: 42px;
            padding: 0 20px;
        }

        /* Estado badges en tabla */
        .badge-activo  { background: #e8f8ed; color: #137333; }
        .badge-suspendido { background: #fff4d6; color: #9a6500; }

        /* Acciones en tabla */
        .acciones-tabla {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .acciones-tabla .boton {
            min-height: 34px;
            padding: 0 12px;
            font-size: 13px;
            margin: 0;
        }

        /* Alerta mensaje */
        .alerta-admin {
            padding: 13px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 700;
        }

        .alerta-admin.exito {
            background: #e9f8ee;
            border: 1px solid #bce8c8;
            color: #177234;
        }

        .alerta-admin.error {
            background: #fff0f0;
            border: 1px solid #ffc9c9;
            color: #b42318;
        }

        /* Tabla vacía */
        .tabla-vacia {
            text-align: center;
            padding: 48px 20px;
            color: var(--muted);
        }

        .tabla-vacia svg {
            margin-bottom: 16px;
            opacity: 0.4;
        }

        .tabla-vacia p {
            margin: 0;
            font-size: 15px;
        }

        @media (max-width: 900px) {
            .admin-sidebar { display: none; }
            .admin-contenido { margin-left: 0; padding: 20px 16px; }
        }

        /* Configuración de Correo */
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
    </style>
</head>
<body>

<div class="admin-app">

    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-marca">
            <img src="imagenes/Logo.png" alt="Logo">
            <strong>INSTITUTO<br>EDUCATIVO</strong>
            <span>Formando Futuro</span>
        </div>

        <div class="admin-badge">&#9679; Panel Administrador</div>

        <nav class="admin-nav">
            <a href="admin_dashboard.php?seccion=profesores" class="<?php echo $seccion === 'profesores' ? 'activo' : ''; ?>">
                <span class="nav-icono">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </span>
                Gestión de Profesores
            </a>
            <a href="admin_dashboard.php?seccion=correo" class="<?php echo $seccion === 'correo' ? 'activo' : ''; ?>">
                <span class="nav-icono">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                </span>
                Configuración de Correo
            </a>
        </nav>

        <div class="admin-perfil" style="justify-content: space-between; width: 100%;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="admin-avatar">
                    <?php 
                    $nombre_admin = $_SESSION['admin_nombre'] ?? 'Administrador';
                    echo htmlspecialchars(strtoupper(substr($nombre_admin, 0, 1))); 
                    ?>
                </div>
                <div>
                    <strong><?php echo htmlspecialchars($nombre_admin); ?></strong>
                    <span>Admin del sistema</span>
                </div>
            </div>
            <a href="logout_admin.php" title="Cerrar sesión" style="color: #ffbaba; display: flex; align-items: center; text-decoration: none; padding: 4px; transition: color 0.15s;" onmouseover="this.style.color='#ff6b6b'" onmouseout="this.style.color='#ffbaba'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </a>
        </div>
    </aside>

    <!-- Contenido -->
    <main class="admin-contenido">

        <?php if ($seccion === 'correo'): ?>
            <!-- Encabezado Correo -->
            <div class="page-head">
                <div>
                    <h1>Configuración de Correo SMTP</h1>
                    <p class="subtitulo">Configura la cuenta de Gmail emisora para los enlaces de restablecimiento de contraseña.</p>
                </div>
            </div>

            <!-- Mensaje de éxito / error -->
            <?php if ($mensaje !== ""): ?>
                <div class="alerta-admin <?php echo $tipo_mensaje; ?>">
                    <?php echo $tipo_mensaje === 'exito' ? '&#10003;' : '&#9888;'; ?>
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 24px; align-items: start;">
                <!-- Formulario de Configuración -->
                <div class="tarjeta" style="padding: 28px;">
                    <h2 style="margin: 0 0 20px; font-size: 16px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--azul-800);">Credenciales SMTP</h2>
                    
                    <form method="POST" action="admin_dashboard.php?seccion=correo">
                        <input type="hidden" name="accion_form" value="configurar_email">
                        
                        <div class="campo-grupo">
                            <label for="smtp_host">Servidor SMTP (Host)</label>
                            <input type="text" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars(SMTP_HOST); ?>" required>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                            <div class="campo-grupo">
                                <label for="smtp_port">Puerto</label>
                                <input type="number" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars(SMTP_PORT); ?>" required>
                            </div>
                            <div class="campo-grupo">
                                <label for="smtp_secure">Seguridad</label>
                                <select id="smtp_secure" name="smtp_secure" class="filtro-select" style="width: 100%; height: 44px; padding: 0 14px;">
                                    <option value="ssl" <?php echo SMTP_SECURE === 'ssl' ? 'selected' : ''; ?>>SSL (Recomendado)</option>
                                    <option value="tls" <?php echo SMTP_SECURE === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                </select>
                            </div>
                        </div>

                        <div class="campo-grupo">
                            <label for="smtp_user">Correo de Gmail Remitente (SMTP User)</label>
                            <input type="email" id="smtp_user" name="smtp_user" value="<?php echo htmlspecialchars(SMTP_USER === 'tu_correo@gmail.com' ? '' : SMTP_USER); ?>" placeholder="ejemplo@gmail.com" required>
                        </div>

                        <div class="campo-grupo" style="position: relative;">
                            <label for="smtp_pass">Contraseña de Aplicación de 16 caracteres (SMTP Pass)</label>
                            <div style="position: relative;">
                                <input type="password" id="smtp_pass" name="smtp_pass" value="<?php echo htmlspecialchars(SMTP_PASS === 'tu_contraseña_de_aplicacion' ? '' : SMTP_PASS); ?>" placeholder="abcd efgh ijkl mnop" required style="padding-right: 44px;">
                                <button type="button" class="toggle-pass" data-target="smtp_pass" title="Mostrar/ocultar" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--muted); display: flex; align-items: center; padding: 0; min-height: unset;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="campo-grupo">
                            <label for="smtp_from_name">Nombre del Remitente</label>
                            <input type="text" id="smtp_from_name" name="smtp_from_name" value="<?php echo htmlspecialchars(SMTP_FROM_NAME); ?>" required>
                        </div>

                        <button type="submit" class="boton" style="width: 100%; margin-top: 10px;">Guardar Configuración</button>
                    </form>
                </div>

                <!-- Test de Envío y Guía -->
                <div style="display: flex; flex-direction: column; gap: 24px;">
                    <!-- Probar Conexión -->
                    <div class="tarjeta" style="padding: 24px;">
                        <h2 style="margin: 0 0 16px; font-size: 15px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--azul-800);">Probar Conexión</h2>
                        <p style="font-size: 13px; color: var(--muted); margin: 0 0 16px; line-height: 1.5;">
                            Envía un correo de prueba rápido a cualquier dirección para verificar si la configuración SMTP actual funciona de verdad.
                        </p>
                        <form method="POST" action="admin_dashboard.php?seccion=correo">
                            <input type="hidden" name="accion_form" value="probar_email">
                            <!-- Copiar credenciales invisibles al enviar para probar en tiempo real antes de guardar si se desea, o usar las guardadas -->
                            <input type="hidden" name="smtp_host" id="test_smtp_host">
                            <input type="hidden" name="smtp_port" id="test_smtp_port">
                            <input type="hidden" name="smtp_secure" id="test_smtp_secure">
                            <input type="hidden" name="smtp_user" id="test_smtp_user">
                            <input type="hidden" name="smtp_pass" id="test_smtp_pass">
                            <input type="hidden" name="smtp_from_name" id="test_smtp_from_name">

                            <div class="campo-grupo">
                                <label for="destinatario_prueba">Enviar correo de prueba a:</label>
                                <input type="email" id="destinatario_prueba" name="destinatario_prueba" placeholder="tu-correo@ejemplo.com" required>
                            </div>
                            <button type="submit" class="boton boton-secundario" onclick="sincronizarCamposTest()" style="width: 100%; margin-top: 8px;">Enviar Correo de Prueba</button>
                        </form>
                    </div>

                    <!-- Guía de Configuración -->
                    <div class="tarjeta" style="padding: 24px; background: #f0f7ff; border: 1px solid #bae6fd;">
                        <h2 style="margin: 0 0 12px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--azul-900);">Guía de Gmail</h2>
                        <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: var(--azul-900); line-height: 1.6;">
                            <li style="margin-bottom: 8px;">Usa tu cuenta institucional o personal de Gmail.</li>
                            <li style="margin-bottom: 8px;">Activa la <strong>Verificación en 2 pasos</strong> en tu Cuenta de Google.</li>
                            <li style="margin-bottom: 8px;">Genera una <strong>Contraseña de Aplicación</strong> en la sección de seguridad de Google.</li>
                            <li style="margin-bottom: 0;">Pega esa contraseña de 16 caracteres en el campo "Contraseña de Aplicación" sin espacios.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <script>
                // Sincronizar los campos del formulario de configuración con el del test para probar sin tener que guardar primero
                function sincronizarCamposTest() {
                    document.getElementById('test_smtp_host').value = document.getElementById('smtp_host').value;
                    document.getElementById('test_smtp_port').value = document.getElementById('smtp_port').value;
                    document.getElementById('test_smtp_secure').value = document.getElementById('smtp_secure').value;
                    document.getElementById('test_smtp_user').value = document.getElementById('smtp_user').value;
                    document.getElementById('test_smtp_pass').value = document.getElementById('smtp_pass').value;
                    document.getElementById('test_smtp_from_name').value = document.getElementById('smtp_from_name').value;
                }
            </script>
        <?php else: ?>
        <!-- Encabezado -->
        <div class="page-head">
            <div>
                <h1>Gestión de Profesores</h1>
                <p class="subtitulo">Administra las cuentas de los docentes del sistema.</p>
            </div>
            <button class="boton" onclick="abrirModalRegistrar()" style="margin-top:0;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Registrar profesor
            </button>
        </div>

        <!-- Mensaje de éxito / error -->
        <?php if ($mensaje !== ""): ?>
            <div class="alerta-admin <?php echo $tipo_mensaje; ?>">
                <?php echo $tipo_mensaje === 'exito' ? '&#10003;' : '&#9888;'; ?>
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <!-- Tarjetas resumen -->
        <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
            <article class="tarjeta stat-card">
                <div class="stat-icono stat-azul">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div>
                    <h3>Total profesores</h3>
                    <strong class="stat-numero"><?php echo $resumen['total'] ?? 0; ?></strong>
                    <small class="stat-trend">Registrados en el sistema</small>
                </div>
            </article>

            <article class="tarjeta stat-card">
                <div class="stat-icono stat-verde">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div>
                    <h3>Activos</h3>
                    <strong class="stat-numero"><?php echo $resumen['activos'] ?? 0; ?></strong>
                    <small class="stat-trend">Con acceso al sistema</small>
                </div>
            </article>

            <article class="tarjeta stat-card">
                <div class="stat-icono stat-amarillo">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <div>
                    <h3>Suspendidos</h3>
                    <strong class="stat-numero"><?php echo $resumen['suspendidos'] ?? 0; ?></strong>
                    <small class="stat-trend">Acceso bloqueado</small>
                </div>
            </article>

            <article class="tarjeta stat-card">
                <div class="stat-icono stat-morado">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                </div>
                <div>
                    <h3>Materias</h3>
                    <strong class="stat-numero"><?php echo $resumen['materias'] ?? 0; ?></strong>
                    <small class="stat-trend">Áreas distintas</small>
                </div>
            </article>
        </div>

        <!-- Tabla de profesores -->
        <div class="tarjeta">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
                <h2 style="margin:0;">Lista de profesores</h2>
            </div>

            <!-- Barra de búsqueda y filtros -->
            <form method="GET" action="admin_dashboard.php" id="form-filtros">
                <div class="barra-herramientas" style="margin-bottom:20px;">
                    <div class="barra-busqueda">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input
                            type="text"
                            name="buscar"
                            placeholder="Buscar por nombre o correo..."
                            value="<?php echo htmlspecialchars($busqueda); ?>"
                            onchange="this.form.submit()"
                        >
                    </div>

                    <select name="materia" class="filtro-select" onchange="this.form.submit()">
                        <option value="">Todas las materias</option>
                        <?php while ($m = mysqli_fetch_assoc($materias_unicas)): ?>
                            <option value="<?php echo htmlspecialchars($m['materia']); ?>"
                                <?php echo $filtro_materia === $m['materia'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($m['materia']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <select name="orden" class="filtro-select" onchange="this.form.submit()">
                        <option value="reciente" <?php echo $orden === 'reciente' ? 'selected' : ''; ?>>Más recientes</option>
                        <option value="az"       <?php echo $orden === 'az'       ? 'selected' : ''; ?>>A → Z</option>
                        <option value="za"       <?php echo $orden === 'za'       ? 'selected' : ''; ?>>Z → A</option>
                        <option value="materia"  <?php echo $orden === 'materia'  ? 'selected' : ''; ?>>Por materia</option>
                    </select>

                    <?php if ($busqueda !== '' || $filtro_materia !== ''): ?>
                        <a href="admin_dashboard.php" class="boton boton-secundario" style="margin:0; min-height:42px; padding: 0 16px; font-size:13px;">
                            Limpiar filtros
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if (mysqli_num_rows($lista_profesores) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Nombre completo</th>
                        <th>Correo institucional</th>
                        <th>N° Empleado</th>
                        <th>Materia / Área</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($prof = mysqli_fetch_assoc($lista_profesores)): ?>
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="width:36px; height:36px; border-radius:50%; background:var(--azul-100); color:var(--azul-600); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:14px; flex-shrink:0;">
                                    <?php echo strtoupper(substr($prof['nombre_completo'], 0, 1)); ?>
                                </div>
                                <strong style="font-size:14px;"><?php echo htmlspecialchars($prof['nombre_completo']); ?></strong>
                            </div>
                        </td>
                        <td style="font-size:14px; color:var(--muted);"><?php echo htmlspecialchars($prof['correo']); ?></td>
                        <td><span class="badge badge-azul"><?php echo htmlspecialchars($prof['numero_empleado']); ?></span></td>
                        <td style="font-size:14px;"><?php echo htmlspecialchars($prof['materia']); ?></td>
                        <td>
                            <?php if ($prof['estado'] === 'activo'): ?>
                                <span class="badge badge-activo">● Activo</span>
                            <?php else: ?>
                                <span class="badge badge-suspendido">⏸ Suspendido</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="acciones-tabla">
                                <!-- Editar -->
                                <button class="boton boton-secundario"
                                    onclick="abrirModalEditar(<?php echo $prof['id_profesor']; ?>, '<?php echo addslashes($prof['nombre_completo']); ?>', '<?php echo addslashes($prof['correo']); ?>', '<?php echo addslashes($prof['numero_empleado']); ?>', '<?php echo addslashes($prof['materia']); ?>')">
                                    Editar
                                </button>

                                <!-- Suspender / Activar -->
                                <?php if ($prof['estado'] === 'activo'): ?>
                                    <a class="boton boton-secundario"
                                       href="admin_dashboard.php?accion=suspender&id=<?php echo $prof['id_profesor']; ?>"
                                       onclick="return confirm('¿Suspender el acceso de este profesor?');">
                                        Suspender
                                    </a>
                                <?php else: ?>
                                    <a class="boton"
                                       href="admin_dashboard.php?accion=activar&id=<?php echo $prof['id_profesor']; ?>"
                                       style="background:var(--verde);">
                                        Activar
                                    </a>
                                <?php endif; ?>

                                <!-- Eliminar -->
                                <a class="boton boton-peligro"
                                   href="admin_dashboard.php?accion=eliminar&id=<?php echo $prof['id_profesor']; ?>"
                                   onclick="return confirm('¿Dar de baja definitivamente a este profesor? Esta acción no se puede deshacer.');">
                                    Eliminar
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="tabla-vacia">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <p><?php echo $busqueda !== '' ? 'No se encontraron profesores con esa búsqueda.' : 'No hay profesores registrados aún.'; ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </main>
</div>

<!-- ═══ Modal: Registrar profesor ═══ -->
<div class="modal-overlay" id="modal-registrar">
    <div class="modal">
        <div class="modal-header">
            <h2>Registrar nuevo profesor</h2>
            <button class="modal-cerrar" onclick="cerrarModal('modal-registrar')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form method="POST" action="admin_dashboard.php" id="form-registrar" novalidate>
            <input type="hidden" name="accion_form" value="registrar">
            <div class="modal-body">
                <div class="campo-grupo">
                    <label for="reg-nombre">Nombre completo *</label>
                    <input type="text" id="reg-nombre" name="nombre_completo" placeholder="Ej. Juan Carlos Pérez López" required>
                </div>
                <div class="campo-grupo">
                    <label for="reg-correo">Correo electrónico institucional *</label>
                    <input type="email" id="reg-correo" name="correo" placeholder="correo@instituto.edu.mx" required>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div class="campo-grupo">
                        <label for="reg-empleado">Número de empleado *</label>
                        <input type="text" id="reg-empleado" name="numero_empleado" placeholder="Ej. EMP-001" required>
                    </div>
                    <div class="campo-grupo">
                        <label for="reg-materia">Materia / Área *</label>
                        <input type="text" id="reg-materia" name="materia" placeholder="Ej. Matemáticas" required>
                    </div>
                </div>
                <div class="campo-grupo">
                    <label for="reg-password">Contraseña temporal *</label>
                    <input type="text" id="reg-password" name="password" placeholder="Se entregará personalmente al docente" required>
                    <small style="color:var(--muted); font-size:12px; margin-top:5px; display:block;">Esta contraseña se enviará directamente al profesor.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="boton boton-secundario" onclick="cerrarModal('modal-registrar')">Cancelar</button>
                <button type="submit" class="boton">Registrar profesor</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══ Modal: Editar profesor ═══ -->
<div class="modal-overlay" id="modal-editar">
    <div class="modal">
        <div class="modal-header">
            <h2>Editar datos del profesor</h2>
            <button class="modal-cerrar" onclick="cerrarModal('modal-editar')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form method="POST" action="admin_dashboard.php" novalidate>
            <input type="hidden" name="accion_form" value="editar">
            <input type="hidden" name="id_profesor" id="edit-id">
            <div class="modal-body">
                <div class="campo-grupo">
                    <label for="edit-nombre">Nombre completo *</label>
                    <input type="text" id="edit-nombre" name="nombre_completo" required>
                </div>
                <div class="campo-grupo">
                    <label for="edit-correo">Correo electrónico institucional *</label>
                    <input type="email" id="edit-correo" name="correo" required>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div class="campo-grupo">
                        <label for="edit-empleado">Número de empleado *</label>
                        <input type="text" id="edit-empleado" name="numero_empleado" required>
                    </div>
                    <div class="campo-grupo">
                        <label for="edit-materia">Materia / Área *</label>
                        <input type="text" id="edit-materia" name="materia" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="boton boton-secundario" onclick="cerrarModal('modal-editar')">Cancelar</button>
                <button type="submit" class="boton">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
    // ── Modales ──
    function abrirModalRegistrar() {
        document.getElementById('modal-registrar').classList.add('abierto');
    }

    function abrirModalEditar(id, nombre, correo, empleado, materia) {
        document.getElementById('edit-id').value      = id;
        document.getElementById('edit-nombre').value  = nombre;
        document.getElementById('edit-correo').value  = correo;
        document.getElementById('edit-empleado').value = empleado;
        document.getElementById('edit-materia').value = materia;
        document.getElementById('modal-editar').classList.add('abierto');
    }

    function cerrarModal(id) {
        document.getElementById(id).classList.remove('abierto');
    }

    // Cerrar modal al hacer clic fuera
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) cerrarModal(this.id);
        });
    });

    // Cerrar con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.abierto').forEach(m => m.classList.remove('abierto'));
        }
    });

    // Abrir modal de editar si viene del GET
    <?php if ($profesor_editar): ?>
        abrirModalEditar(
            <?php echo $profesor_editar['id_profesor']; ?>,
            '<?php echo addslashes($profesor_editar['nombre_completo']); ?>',
            '<?php echo addslashes($profesor_editar['correo']); ?>',
            '<?php echo addslashes($profesor_editar['numero_empleado']); ?>',
            '<?php echo addslashes($profesor_editar['materia']); ?>'
        );
    <?php endif; ?>

    // Auto-ocultar mensaje después de 4 segundos
    const alerta = document.querySelector('.alerta-admin');
    if (alerta) {
        setTimeout(() => {
            alerta.style.transition = 'opacity 0.5s';
            alerta.style.opacity = '0';
            setTimeout(() => alerta.remove(), 500);
        }, 4000);
    }

    // ── Toggle mostrar/ocultar contraseña para SMTP ──
    document.querySelectorAll('.toggle-pass').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const input = document.getElementById(targetId);
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    });
</script>

</body>
</html>
