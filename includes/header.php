<?php
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

            <div class="perfil">
                <div class="avatar">P</div>
                <div>
                    <strong>Profesor</strong>
                    <span>Docente</span>
                </div>
            </div>
        </aside>

        <main class="contenido">
