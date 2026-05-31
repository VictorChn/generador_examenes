<?php
include 'config/conexion.php';
include 'includes/header.php';

$consulta_resumen = mysqli_query($conexion, "
    SELECT
        COUNT(*) AS total_examenes,
        COALESCE(SUM(total_preguntas), 0) AS total_preguntas
    FROM examenes
");
$resumen = mysqli_fetch_assoc($consulta_resumen);

$consulta_aplicados = mysqli_query($conexion, "
    SELECT COUNT(DISTINCT id_examen) AS aplicados
    FROM resultados
");
$aplicados = mysqli_fetch_assoc($consulta_aplicados);

$consulta_examenes = mysqli_query($conexion, "
    SELECT
        examenes.id_examen,
        examenes.titulo,
        examenes.total_preguntas,
        examenes.fecha_creacion,
        COUNT(resultados.id_resultado) AS alumnos_evaluados
    FROM examenes
    LEFT JOIN resultados ON examenes.id_examen = resultados.id_examen
    GROUP BY examenes.id_examen, examenes.titulo, examenes.total_preguntas, examenes.fecha_creacion
    ORDER BY examenes.id_examen DESC
");
?>

<div class="page-head">
    <div>
        <h1>Examenes generados</h1>
        <p>Consulta y administra los examenes que has generado.</p>
    </div>
    <a class="boton" href="generar_examen.php">+ Generar nuevo examen</a>
</div>

<section class="stats-grid">
    <article class="tarjeta stat-card">
        <div class="stat-icono stat-azul">#</div>
        <div>
            <h3>Total de examenes</h3>
            <strong class="stat-numero"><?php echo $resumen['total_examenes'] ?? 0; ?></strong>
            <small>Todos los examenes generados</small>
        </div>
    </article>

    <article class="tarjeta stat-card">
        <div class="stat-icono stat-verde">OK</div>
        <div>
            <h3>Aplicados</h3>
            <strong class="stat-numero"><?php echo $aplicados['aplicados'] ?? 0; ?></strong>
            <small>Con resultados guardados</small>
        </div>
    </article>

    <article class="tarjeta stat-card">
        <div class="stat-icono stat-amarillo">?</div>
        <div>
            <h3>Pendientes</h3>
            <strong class="stat-numero"><?php echo max(0, ($resumen['total_examenes'] ?? 0) - ($aplicados['aplicados'] ?? 0)); ?></strong>
            <small>Sin respuestas registradas</small>
        </div>
    </article>

    <article class="tarjeta stat-card">
        <div class="stat-icono stat-morado">P</div>
        <div>
            <h3>Preguntas asignadas</h3>
            <strong class="stat-numero"><?php echo $resumen['total_preguntas'] ?? 0; ?></strong>
            <small>En todos los examenes</small>
        </div>
    </article>
</section>

<section class="tarjeta">
    <h2>Listado de examenes</h2>

    <?php if ($consulta_examenes && mysqli_num_rows($consulta_examenes) > 0) { ?>
        <table>
            <thead>
                <tr>
                    <th>Codigo</th>
                    <th>Nombre del examen</th>
                    <th>Preguntas</th>
                    <th>Fecha de generacion</th>
                    <th>Estado</th>
                    <th>Alumnos evaluados</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($examen = mysqli_fetch_assoc($consulta_examenes)) { ?>
                    <tr>
                        <td><strong>EX-<?php echo str_pad($examen['id_examen'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                        <td><?php echo htmlspecialchars($examen['titulo']); ?></td>
                        <td><?php echo $examen['total_preguntas']; ?></td>
                        <td><?php echo $examen['fecha_creacion']; ?></td>
                        <td>
                            <?php if ($examen['alumnos_evaluados'] > 0) { ?>
                                <span class="badge badge-verde">Aplicado</span>
                            <?php } else { ?>
                                <span class="badge badge-amarillo">Pendiente</span>
                            <?php } ?>
                        </td>
                        <td><?php echo $examen['alumnos_evaluados']; ?></td>
                        <td>
                            <div class="acciones">
                                <a class="boton boton-secundario" href="examen.php?id=<?php echo $examen['id_examen']; ?>">Ver</a>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <p class="subtitulo">Todavia no hay examenes generados.</p>
    <?php } ?>
</section>

<?php include 'includes/footer.php'; ?>
