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

$actividad = mysqli_query($conexion, "
    SELECT titulo AS detalle, fecha_creacion AS fecha, 'Examen generado' AS actividad
    FROM examenes
    ORDER BY fecha_creacion DESC
    LIMIT 5
");
?>

<div class="page-head">
    <div>
        <h1>Bienvenido, Profesor</h1>
        <p>Aqui tienes un resumen de tu actividad academica.</p>
    </div>
    <a class="boton boton-secundario" href="generar_examen.php">+ Generar nuevo examen</a>
</div>

<section class="stats-grid">
    <article class="tarjeta stat-card">
        <div class="stat-icono stat-azul">+</div>
        <div>
            <h3>Examenes generados</h3>
            <strong class="stat-numero"><?php echo $total_examenes; ?></strong>
            <small>Registrados en el sistema</small>
        </div>
    </article>

    <article class="tarjeta stat-card">
        <div class="stat-icono stat-verde">P</div>
        <div>
            <h3>Alumnos evaluados</h3>
            <strong class="stat-numero"><?php echo $total_resultados; ?></strong>
            <small>Resultados guardados</small>
        </div>
    </article>

    <article class="tarjeta stat-card">
        <div class="stat-icono stat-amarillo">%</div>
        <div>
            <h3>Promedio general</h3>
            <strong class="stat-numero"><?php echo number_format($promedio_general, 0); ?>%</strong>
            <small>Calificacion promedio</small>
        </div>
    </article>

    <article class="tarjeta stat-card">
        <div class="stat-icono stat-morado">?</div>
        <div>
            <h3>Preguntas en banco</h3>
            <strong class="stat-numero"><?php echo $total_preguntas; ?></strong>
            <small><?php echo $total_temas; ?> temas registrados</small>
        </div>
    </article>
</section>

<section class="panel-grid">
    <article class="tarjeta">
        <h2>Actividad reciente</h2>
        <?php if ($actividad && mysqli_num_rows($actividad) > 0) { ?>
            <table>
                <thead>
                    <tr>
                        <th>Actividad</th>
                        <th>Detalle</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($fila = mysqli_fetch_assoc($actividad)) { ?>
                        <tr>
                            <td><span class="badge badge-azul"><?php echo htmlspecialchars($fila['actividad']); ?></span></td>
                            <td><?php echo htmlspecialchars($fila['detalle']); ?></td>
                            <td><?php echo $fila['fecha']; ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <p class="subtitulo">Todavia no hay examenes generados.</p>
        <?php } ?>
    </article>

    <article class="tarjeta">
        <h2>Accesos rapidos</h2>
        <div class="quick-list">
            <a class="quick-item" href="generar_examen.php">
                <span class="stat-icono stat-azul">+</span>
                <span><strong>Generar nuevo examen</strong><span>Crea un examen aleatorio</span></span>
            </a>
            <a class="quick-item" href="agregar_pregunta.php">
                <span class="stat-icono stat-verde">?</span>
                <span><strong>Agregar pregunta</strong><span>Anade nuevas preguntas al banco</span></span>
            </a>
            <a class="quick-item" href="preguntas.php">
                <span class="stat-icono stat-amarillo">#</span>
                <span><strong>Banco de preguntas</strong><span>Consulta y administra preguntas</span></span>
            </a>
            <a class="quick-item" href="estadisticas.php">
                <span class="stat-icono stat-morado">%</span>
                <span><strong>Ver estadisticas</strong><span>Analiza el rendimiento por tema</span></span>
            </a>
        </div>
    </article>
</section>

<?php include 'includes/footer.php'; ?>
