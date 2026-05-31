<?php
include 'config/conexion.php';
include 'includes/header.php';

$sql_estadisticas = "
    SELECT
        temas.nombre AS tema,
        COUNT(respuestas_estudiante.id_respuesta) AS total_respuestas,
        SUM(respuestas_estudiante.es_correcta) AS respuestas_correctas,
        COUNT(respuestas_estudiante.id_respuesta) - SUM(respuestas_estudiante.es_correcta) AS respuestas_incorrectas,
        (SUM(respuestas_estudiante.es_correcta) / COUNT(respuestas_estudiante.id_respuesta)) * 100 AS porcentaje_acierto
    FROM respuestas_estudiante
    INNER JOIN preguntas ON respuestas_estudiante.id_pregunta = preguntas.id_pregunta
    INNER JOIN temas ON preguntas.id_tema = temas.id_tema
    GROUP BY temas.id_tema, temas.nombre
    ORDER BY porcentaje_acierto DESC
";

$estadisticas = mysqli_query($conexion, $sql_estadisticas);

$sql_general = "
    SELECT
        COUNT(id_resultado) AS examenes_contestados,
        AVG(calificacion) AS promedio_general,
        MAX(calificacion) AS mejor_calificacion,
        MIN(calificacion) AS menor_calificacion
    FROM resultados
";

$consulta_general = mysqli_query($conexion, $sql_general);
$general = mysqli_fetch_assoc($consulta_general);
?>

<div class="page-head">
    <div>
        <h1>Estadisticas de desempeno</h1>
        <p>Analiza el rendimiento por tema en los examenes aplicados.</p>
    </div>
    <a class="boton boton-secundario" href="javascript:window.print();">Exportar reporte</a>
</div>

<section class="stats-grid">
    <article class="tarjeta stat-card">
        <div class="stat-icono stat-azul">P</div>
        <div>
        <h3>Examenes contestados</h3>
        <strong class="stat-numero"><?php echo $general['examenes_contestados'] ?? 0; ?></strong>
        </div>
    </article>

    <article class="tarjeta stat-card">
        <div class="stat-icono stat-verde">%</div>
        <div>
        <h3>Promedio general</h3>
        <strong class="stat-numero"><?php echo number_format($general['promedio_general'] ?? 0, 0); ?>%</strong>
        </div>
    </article>

    <article class="tarjeta stat-card">
        <div class="stat-icono stat-amarillo">+</div>
        <div>
        <h3>Mejor calificacion</h3>
        <strong class="stat-numero"><?php echo number_format($general['mejor_calificacion'] ?? 0, 0); ?>%</strong>
        </div>
    </article>

    <article class="tarjeta stat-card">
        <div class="stat-icono stat-rojo">-</div>
        <div>
        <h3>Menor calificacion</h3>
        <strong class="stat-numero"><?php echo number_format($general['menor_calificacion'] ?? 0, 0); ?>%</strong>
        </div>
    </article>
</section>

<section class="tarjeta">
    <h2>Desempeno por tema</h2>

    <?php if ($estadisticas && mysqli_num_rows($estadisticas) > 0) { ?>
        <table>
            <thead>
                <tr>
                    <th>Tema</th>
                    <th>Total de respuestas</th>
                    <th>Correctas</th>
                    <th>Incorrectas</th>
                    <th>Porcentaje de acierto</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($fila = mysqli_fetch_assoc($estadisticas)) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($fila['tema']); ?></td>
                        <td><?php echo $fila['total_respuestas']; ?></td>
                        <td><?php echo $fila['respuestas_correctas']; ?></td>
                        <td><?php echo $fila['respuestas_incorrectas']; ?></td>
                        <td>
                            <?php
                            $porcentaje = $fila['porcentaje_acierto'];
                            $clase = 'badge-rojo';
                            $nivel = 'Bajo';
                            if ($porcentaje >= 80) {
                                $clase = 'badge-verde';
                                $nivel = 'Alto';
                            } elseif ($porcentaje >= 50) {
                                $clase = 'badge-amarillo';
                                $nivel = 'Medio';
                            }
                            ?>
                            <span class="badge <?php echo $clase; ?>">
                                <?php echo number_format($porcentaje, 2); ?>% - <?php echo $nivel; ?>
                            </span>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <p>Todavia no hay respuestas registradas para generar estadisticas.</p>
    <?php } ?>
</section>

<?php include 'includes/footer.php'; ?>
