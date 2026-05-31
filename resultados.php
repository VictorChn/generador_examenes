<?php
include 'config/conexion.php';
include 'includes/header.php';

$consulta_resultados = mysqli_query($conexion, "
    SELECT
        resultados.id_resultado,
        resultados.nombre_estudiante,
        resultados.total_preguntas,
        resultados.respuestas_correctas,
        resultados.calificacion,
        resultados.fecha_presentacion,
        examenes.id_examen,
        examenes.titulo
    FROM resultados
    INNER JOIN examenes ON resultados.id_examen = examenes.id_examen
    ORDER BY resultados.id_resultado DESC
");

$consulta_resumen = mysqli_query($conexion, "
    SELECT
        COUNT(*) AS total_resultados,
        AVG(calificacion) AS promedio,
        MAX(calificacion) AS mejor,
        MIN(calificacion) AS menor
    FROM resultados
");
$resumen = mysqli_fetch_assoc($consulta_resumen);
?>

<div class="page-head">
    <div>
        <h1>Resultados</h1>
        <p>Consulta los examenes contestados y sus calificaciones.</p>
    </div>
    <a class="boton" href="generar_examen.php">Generar examen</a>
</div>

<section class="stats-grid">
    <article class="tarjeta stat-card">
        <div class="stat-icono stat-azul">P</div>
        <div>
            <h3>Resultados guardados</h3>
            <strong class="stat-numero"><?php echo $resumen['total_resultados'] ?? 0; ?></strong>
        </div>
    </article>

    <article class="tarjeta stat-card">
        <div class="stat-icono stat-verde">%</div>
        <div>
            <h3>Promedio</h3>
            <strong class="stat-numero"><?php echo number_format($resumen['promedio'] ?? 0, 0); ?>%</strong>
        </div>
    </article>

    <article class="tarjeta stat-card">
        <div class="stat-icono stat-amarillo">+</div>
        <div>
            <h3>Mejor calificacion</h3>
            <strong class="stat-numero"><?php echo number_format($resumen['mejor'] ?? 0, 0); ?>%</strong>
        </div>
    </article>

    <article class="tarjeta stat-card">
        <div class="stat-icono stat-rojo">-</div>
        <div>
            <h3>Menor calificacion</h3>
            <strong class="stat-numero"><?php echo number_format($resumen['menor'] ?? 0, 0); ?>%</strong>
        </div>
    </article>
</section>

<section class="tarjeta">
    <h2>Listado de resultados</h2>

    <?php if ($consulta_resultados && mysqli_num_rows($consulta_resultados) > 0) { ?>
        <table>
            <thead>
                <tr>
                    <th>Estudiante</th>
                    <th>Examen</th>
                    <th>Codigo</th>
                    <th>Aciertos</th>
                    <th>Calificacion</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($resultado = mysqli_fetch_assoc($consulta_resultados)) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($resultado['nombre_estudiante']); ?></td>
                        <td><?php echo htmlspecialchars($resultado['titulo']); ?></td>
                        <td>EX-<?php echo str_pad($resultado['id_examen'], 4, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo $resultado['respuestas_correctas']; ?> / <?php echo $resultado['total_preguntas']; ?></td>
                        <td><span class="badge badge-azul"><?php echo number_format($resultado['calificacion'], 2); ?>%</span></td>
                        <td><?php echo $resultado['fecha_presentacion']; ?></td>
                        <td>
                            <div class="acciones">
                                <a class="boton boton-secundario" href="resultado.php?id=<?php echo $resultado['id_resultado']; ?>">Ver</a>
                                <a class="boton boton-secundario" href="exportar_pdf.php?id=<?php echo $resultado['id_resultado']; ?>">PDF</a>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <p class="subtitulo">Todavia no hay resultados. Primero genera y contesta un examen.</p>
    <?php } ?>
</section>

<?php include 'includes/footer.php'; ?>
