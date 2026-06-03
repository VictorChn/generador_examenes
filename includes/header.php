<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['profesor_id'])) {
    header("Location: login.php");
    exit;
}

$pagina_actual = basename($_SERVER['PHP_SELF']);

function menu_activo($archivo, $pagina_actual)
{
    return $archivo == $pagina_actual ? ' activo' : '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador y evaluador automatico de examenes</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <div class="app">
        <aside class="sidebar">
            <div class="marca">
                <div style="display: flex; justify-content: center; margin-bottom: 12px;">
                    <img src="imagenes/Logo.png" alt="Logo Instituto" style="width: 300px; height: auto; mix-blend-mode: lighten;">
                </div>
                <div class="marca-texto">
                    <strong>INSTITUTO<br>EDUCATIVO</strong>
                    <span>FORMANDO FUTURO</span>
                </div>
            </div>

            <nav class="menu">
                <a class="<?php echo menu_activo('dashboard.php', $pagina_actual); ?>" href="dashboard.php">
                    <span class="menu-icono">[]</span> Dashboard
                </a>
                <a class="<?php echo menu_activo('preguntas.php', $pagina_actual) . menu_activo('agregar_pregunta.php', $pagina_actual) . menu_activo('editar_pregunta.php', $pagina_actual); ?>" href="preguntas.php">
                    <span class="menu-icono">?</span> Banco de preguntas
                </a>
                <a class="<?php echo menu_activo('generar_examen.php', $pagina_actual); ?>" href="generar_examen.php">
                    <span class="menu-icono">+</span> Generar examen
                </a>
                <a class="<?php echo menu_activo('examenes_generados.php', $pagina_actual) . menu_activo('examen.php', $pagina_actual); ?>" href="examenes_generados.php">
                    <span class="menu-icono">#</span> Examenes generados
                </a>
                <a class="<?php echo menu_activo('resultados.php', $pagina_actual) . menu_activo('resultado.php', $pagina_actual); ?>" href="resultados.php">
                    <span class="menu-icono">=</span> Resultados
                </a>
                <a class="<?php echo menu_activo('estadisticas.php', $pagina_actual); ?>" href="estadisticas.php">
                    <span class="menu-icono">%</span> Estadisticas
                </a>
            </nav>

            <div class="perfil" style="justify-content: space-between; width: 100%;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div class="avatar">
                        <?php 
                        $nombre_prof = $_SESSION['profesor_nombre'] ?? 'Profesor';
                        echo htmlspecialchars(strtoupper(substr($nombre_prof, 0, 1))); 
                        ?>
                    </div>
                    <div>
                        <strong><?php echo htmlspecialchars($nombre_prof); ?></strong>
                        <span style="font-size: 11.5px; color: #cbd6e7;"><?php echo htmlspecialchars($_SESSION['profesor_materia'] ?? 'Docente'); ?></span>
                    </div>
                </div>
                <a href="logout.php" title="Cerrar sesión" style="color: #ffbaba; display: flex; align-items: center; text-decoration: none; padding: 4px; transition: color 0.15s;" onmouseover="this.style.color='#ff6b6b'" onmouseout="this.style.color='#ffbaba'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </a>
            </div>
        </aside>

        <main class="contenido">
