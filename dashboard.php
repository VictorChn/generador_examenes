<?php
include 'config/conexion.php';
include 'includes/header.php';

$total_temas = 0;
$total_preguntas = 0;
$total_examenes = 0;
$total_resultados = 0;
$promedio_general = 0;

$consulta_temas = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM temas");
$consulta_preguntas = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM preguntas");
$consulta_examenes = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM examenes");
$consulta_resultados = mysqli_query($conexion, "SELECT COUNT(*) AS total, AVG(calificacion) AS promedio FROM resultados");

if ($consulta_temas) $total_temas = mysqli_fetch_assoc($consulta_temas)['total'];
if ($consulta_preguntas) $total_preguntas = mysqli_fetch_assoc($consulta_preguntas)['total'];
if ($consulta_examenes) $total_examenes = mysqli_fetch_assoc($consulta_examenes)['total'];
if ($consulta_resultados) {
    $fila_resultados = mysqli_fetch_assoc($consulta_resultados);
    $total_resultados = $fila_resultados['total'];
    $promedio_general = $fila_resultados['promedio'] ?? 0;
}

// Lógica para Exámenes por estado
$consulta_estados = mysqli_query($conexion, "
    SELECT 
        SUM(CASE WHEN e.id_examen IN (SELECT id_examen FROM resultados) THEN 1 ELSE 0 END) AS aplicados,
        SUM(CASE WHEN e.id_examen NOT IN (SELECT id_examen FROM resultados) THEN 1 ELSE 0 END) AS pendientes
    FROM examenes e
");
$aplicados = 0;
$pendientes = 0;
$borradores = 0;
if ($consulta_estados && $fila_estados = mysqli_fetch_assoc($consulta_estados)) {
    $aplicados = $fila_estados['aplicados'] ?? 0;
    $pendientes = $fila_estados['pendientes'] ?? 0;
}
$total_estados = $aplicados + $pendientes + $borradores;
$porcentaje_aplicados = $total_estados > 0 ? round(($aplicados / $total_estados) * 100) : 0;
$porcentaje_pendientes = $total_estados > 0 ? round(($pendientes / $total_estados) * 100) : 0;
$porcentaje_borradores = $total_estados > 0 ? round(($borradores / $total_estados) * 100) : 0;

$gradiente_chart = "#e0e0e0 0% 100%"; // Estado vacío
if ($total_estados > 0) {
    $p1 = $porcentaje_aplicados;
    $p2 = $p1 + $porcentaje_pendientes;
    $gradiente_chart = "var(--verde) 0% {$p1}%, var(--amarillo) {$p1}% {$p2}%, var(--rojo) {$p2}% 100%";
}

// Lógica para Rendimiento general
$consulta_rendimiento = mysqli_query($conexion, "
    SELECT DATE(fecha_presentacion) as fecha, AVG(calificacion) as promedio
    FROM resultados
    GROUP BY DATE(fecha_presentacion)
    ORDER BY fecha ASC
    LIMIT 6
");
$datos_rendimiento = [];
if ($consulta_rendimiento) {
    while ($row = mysqli_fetch_assoc($consulta_rendimiento)) {
<<<<<<< HEAD
=======
        // La calificación es sobre 10 o 100? En la base de datos es DECIMAL(5,2). Asumiremos sobre 100.
        // Si calificacion máxima es 10, lo multiplicamos por 10. Si es 100 lo dejamos.
        // Por seguridad, si el promedio es <= 10, asumimos base 10.
>>>>>>> b0ce2526d8b7fbcd61a207990251e08376002d93
        $promedio_calc = $row['promedio'];
        if ($promedio_calc <= 10 && $promedio_calc > 0) {
            $promedio_calc *= 10; 
        }
        $datos_rendimiento[] = [
            'fecha' => date('d M', strtotime($row['fecha'])),
            'promedio' => round($promedio_calc)
        ];
    }
}

$actividad = mysqli_query($conexion, "
    SELECT titulo AS detalle, fecha_creacion AS fecha, 'Examen generado' AS actividad
    FROM examenes
    ORDER BY fecha_creacion DESC
    LIMIT 5
");
?>

<div class="page-head">
    <div>
        <h1>¡Bienvenido, Profesor!</h1>
        <p>Aquí tienes un resumen de tu actividad académica.</p>
    </div>
    <div class="date-picker">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#5b6b86" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
        <span>20 de mayo de 2024</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#5b6b86" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
    </div>
</div>

<section class="stats-grid">
    <article class="tarjeta stat-card">
        <div class="stat-icono stat-azul">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
        </div>
        <div class="stat-info">
            <h3>Exámenes generados</h3>
            <strong class="stat-numero"><?php echo $total_examenes; ?></strong>
            <small class="stat-trend">+5 este mes</small>
        </div>
    </article>

    <article class="tarjeta stat-card">
        <div class="stat-icono stat-verde">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </div>
        <div class="stat-info">
            <h3>Alumnos evaluados</h3>
            <strong class="stat-numero"><?php echo $total_resultados; ?></strong>
            <small class="stat-trend">+28 este mes</small>
        </div>
    </article>

    <article class="tarjeta stat-card">
        <div class="stat-icono stat-amarillo">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        </div>
        <div class="stat-info">
            <h3>Promedio general</h3>
            <strong class="stat-numero"><?php echo number_format($promedio_general, 0); ?>%</strong>
            <small class="stat-trend">+4% este mes</small>
        </div>
    </article>

    <article class="tarjeta stat-card">
        <div class="stat-icono stat-morado">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="18" y="3" width="4" height="18"></rect><rect x="10" y="8" width="4" height="13"></rect><rect x="2" y="13" width="4" height="8"></rect></svg>
        </div>
        <div class="stat-info">
            <h3>Preguntas en banco</h3>
            <strong class="stat-numero"><?php echo $total_preguntas; ?></strong>
            <small class="stat-trend">+32 este mes</small>
        </div>
    </article>
</section>

<section class="dashboard-grid">
    <article class="tarjeta">
        <h2>Actividad reciente</h2>
        <?php if ($actividad && mysqli_num_rows($actividad) > 0) { ?>
            <table class="actividad-tabla">
                <thead>
                    <tr>
                        <th>Actividad</th>
                        <th>Detalle</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($fila = mysqli_fetch_assoc($actividad)) { 
                        $icono_clase = "stat-azul";
                        $icono_svg = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>';
                        if (strpos(strtolower($fila['actividad']), 'resultados') !== false) {
                            $icono_clase = "stat-verde";
                            $icono_svg = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>';
                        }
                    ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px; font-weight: 600;">
                                    <div class="stat-icono <?php echo $icono_clase; ?>" style="width: 28px; height: 28px; border-radius: 6px;">
                                        <?php echo $icono_svg; ?>
                                    </div>
                                    <?php echo htmlspecialchars($fila['actividad']); ?>
                                </div>
                            </td>
                            <td style="color: #273650; font-weight: 500;"><?php echo htmlspecialchars($fila['detalle']); ?></td>
                            <td style="color: var(--muted);"><?php echo date('d/m/Y h:i a', strtotime($fila['fecha'])); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <p class="subtitulo">Todavía no hay actividades registradas.</p>
        <?php } ?>
        <a href="#" class="link-azul">Ver todas las actividades ></a>
    </article>

    <article class="tarjeta">
        <h2>Exámenes por estado</h2>
        <div class="estado-chart-container">
            <div class="doughnut-chart" style="background: conic-gradient(<?php echo $gradiente_chart; ?>);">
                <div class="doughnut-inner">
                    <strong><?php echo $total_estados; ?></strong>
                    <span>Total</span>
                </div>
            </div>
            <div class="estado-legend">
                <div class="legend-item">
                    <span class="dot bg-verde"></span>
                    <span>Aplicados</span>
                    <strong><?php echo $aplicados; ?> (<?php echo $porcentaje_aplicados; ?>%)</strong>
                </div>
                <div class="legend-item">
                    <span class="dot bg-amarillo"></span>
                    <span>Pendientes</span>
                    <strong><?php echo $pendientes; ?> (<?php echo $porcentaje_pendientes; ?>%)</strong>
                </div>
                <div class="legend-item">
                    <span class="dot bg-rojo"></span>
                    <span>Borradores</span>
                    <strong><?php echo $borradores; ?> (<?php echo $porcentaje_borradores; ?>%)</strong>
                </div>
            </div>
        </div>
        <a href="examenes_generados.php" class="link-azul">Ver todos los exámenes ></a>
    </article>

    <article class="tarjeta">
        <h2>Rendimiento general</h2>
        <div class="chart-legend">
            <span class="legend-blue"><span class="dot"></span> Promedio general</span>
        </div>
        <div class="chart-mockup">
            <?php if (count($datos_rendimiento) == 0) { ?>
                <p class="subtitulo" style="padding: 40px 0; text-align: center;">Todavía no hay resultados registrados para mostrar el rendimiento.</p>
            <?php } else { ?>
            <svg viewBox="0 0 800 250" width="100%" height="250">
                <line x1="40" y1="20" x2="780" y2="20" stroke="#f0f0f0" stroke-width="1"/>
                <line x1="40" y1="70" x2="780" y2="70" stroke="#f0f0f0" stroke-width="1"/>
                <line x1="40" y1="120" x2="780" y2="120" stroke="#f0f0f0" stroke-width="1"/>
                <line x1="40" y1="170" x2="780" y2="170" stroke="#f0f0f0" stroke-width="1"/>
                <line x1="40" y1="220" x2="780" y2="220" stroke="#f0f0f0" stroke-width="1"/>
                
                <text x="30" y="24" font-size="12" fill="#888" text-anchor="end">100%</text>
                <text x="30" y="74" font-size="12" fill="#888" text-anchor="end">75%</text>
                <text x="30" y="124" font-size="12" fill="#888" text-anchor="end">50%</text>
                <text x="30" y="174" font-size="12" fill="#888" text-anchor="end">25%</text>
                <text x="30" y="224" font-size="12" fill="#888" text-anchor="end">0%</text>
                
                <?php 
                $num_puntos = count($datos_rendimiento);
                $espacio_x = 700 / max(1, $num_puntos - 1);
                $puntos_svg = [];
                foreach ($datos_rendimiento as $i => $dato) {
                    $x = 80 + ($i * $espacio_x);
                    $y = 220 - ($dato['promedio'] * 2);
                    $puntos_svg[] = "$x,$y";
                    echo "<text x=\"$x\" y=\"240\" font-size=\"12\" fill=\"#888\" text-anchor=\"middle\">{$dato['fecha']}</text>";
                }
                
                if ($num_puntos > 1) {
                    $polyline_str = implode(" ", $puntos_svg);
                    echo "<polyline points=\"$polyline_str\" fill=\"none\" stroke=\"#0057ff\" stroke-width=\"3\"/>";
                }
                
                foreach ($datos_rendimiento as $i => $dato) {
                    $x = 80 + ($i * $espacio_x);
                    $y = 220 - ($dato['promedio'] * 2);
                    echo "<circle cx=\"$x\" cy=\"$y\" r=\"5\" fill=\"#0057ff\"/>";
                }
                ?>
            </svg>
            <?php } ?>
        </div>
    </article>

    <article class="tarjeta">
        <h2>Accesos rápidos</h2>
        <div class="quick-list">
            <a class="quick-item" href="generar_examen.php">
                <span class="stat-icono stat-azul">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                </span>
                <div class="quick-item-content">
                    <strong>Generar nuevo examen</strong>
                    <span>Crea un examen a partir de tu banco de preguntas</span>
                </div>
                <svg class="quick-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>
            <a class="quick-item" href="agregar_pregunta.php">
                <span class="stat-icono stat-verde">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                </span>
                <div class="quick-item-content">
                    <strong>Agregar pregunta</strong>
                    <span>Añade nuevas preguntas al banco</span>
                </div>
                <svg class="quick-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>
            <a class="quick-item" href="resultados.php">
                <span class="stat-icono stat-amarillo">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line><path d="M9 16l2 2 4-4"></path></svg>
                </span>
                <div class="quick-item-content">
                    <strong>Ver resultados</strong>
                    <span>Consulta los resultados de tus exámenes</span>
                </div>
                <svg class="quick-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>
            <a class="quick-item" href="estadisticas.php">
                <span class="stat-icono stat-morado">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="18" y="3" width="4" height="18"></rect><rect x="10" y="8" width="4" height="13"></rect><rect x="2" y="13" width="4" height="8"></rect></svg>
                </span>
                <div class="quick-item-content">
                    <strong>Ver estadísticas</strong>
                    <span>Analiza el rendimiento de tus alumnos</span>
                </div>
                <svg class="quick-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>
        </div>
    </article>
</section>

<?php include 'includes/footer.php'; ?>
<<<<<<< HEAD
=======

>>>>>>> b0ce2526d8b7fbcd61a207990251e08376002d93
