<?php
include 'config/conexion.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id_resultado = $_GET['id'];

$sql_resultado = "
    SELECT
        resultados.*,
        examenes.titulo
    FROM resultados
    INNER JOIN examenes ON resultados.id_examen = examenes.id_examen
    WHERE resultados.id_resultado = ?
";

$stmt_resultado = mysqli_prepare($conexion, $sql_resultado);
mysqli_stmt_bind_param($stmt_resultado, "i", $id_resultado);
mysqli_stmt_execute($stmt_resultado);
$consulta_resultado = mysqli_stmt_get_result($stmt_resultado);
$resultado = mysqli_fetch_assoc($consulta_resultado);

if (!$resultado) {
    header("Location: index.php");
    exit;
}

$sql_detalle = "
    SELECT
        respuestas_estudiante.respuesta_estudiante,
        respuestas_estudiante.es_correcta,
        preguntas.enunciado,
        preguntas.respuesta_correcta,
        temas.nombre AS tema
    FROM respuestas_estudiante
    INNER JOIN preguntas ON respuestas_estudiante.id_pregunta = preguntas.id_pregunta
    INNER JOIN temas ON preguntas.id_tema = temas.id_tema
    WHERE respuestas_estudiante.id_resultado = ?
";

$stmt_detalle = mysqli_prepare($conexion, $sql_detalle);
mysqli_stmt_bind_param($stmt_detalle, "i", $id_resultado);
mysqli_stmt_execute($stmt_detalle);
$detalle = mysqli_stmt_get_result($stmt_detalle);

include 'includes/header.php';
?>

<section class="resultado-hero">
    <div class="resultado-ok">OK</div>
    <h1>Examen finalizado</h1>
    <p class="subtitulo">Aqui estan tus resultados.</p>
</section>

<section class="tarjeta">
    <div class="grid">
        <div>
            <span class="subtitulo">Alumno</span>
            <h2><?php echo htmlspecialchars($resultado['nombre_estudiante']); ?></h2>
        </div>
        <div>
            <span class="subtitulo">Codigo del examen</span>
            <h2>EX-<?php echo str_pad($resultado['id_examen'], 4, '0', STR_PAD_LEFT); ?></h2>
        </div>
        <div>
            <span class="subtitulo">Fecha</span>
            <h2><?php echo $resultado['fecha_presentacion']; ?></h2>
        </div>
    </div>
</section>

<section class="tarjeta dos-columnas">
    <div>
        <h2>Tu calificacion</h2>
        <div class="calificacion-circulo"><?php echo number_format($resultado['calificacion'] / 10, 1); ?></div>
        <span class="badge badge-verde">Resultado sobre 10</span>
    </div>
    <div>
        <h2>Resumen de resultados</h2>
        <table>
            <tr>
                <td>Total de preguntas</td>
                <td><strong><?php echo $resultado['total_preguntas']; ?></strong></td>
            </tr>
            <tr>
                <td>Total de aciertos</td>
                <td><strong class="badge badge-verde"><?php echo $resultado['respuestas_correctas']; ?></strong></td>
            </tr>
            <tr>
                <td>Total de errores</td>
                <td><strong class="badge badge-rojo"><?php echo $resultado['total_preguntas'] - $resultado['respuestas_correctas']; ?></strong></td>
            </tr>
            <tr>
                <td>Porcentaje obtenido</td>
                <td><strong><?php echo number_format($resultado['calificacion'], 2); ?>%</strong></td>
            </tr>
        </table>
        <div class="acciones">
            <a class="boton" href="exportar_pdf.php?id=<?php echo $resultado['id_resultado']; ?>">Exportar resultado en PDF</a>
            <a class="boton boton-secundario" href="index.php">Volver al inicio</a>
        </div>
    </div>
</section>

<section class="tarjeta">
    <h2>Detalle de respuestas</h2>

    <table>
        <thead>
            <tr>
                <th>Tema</th>
                <th>Pregunta</th>
                <th>Tu respuesta</th>
                <th>Respuesta correcta</th>
                <th>Resultado</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($fila = mysqli_fetch_assoc($detalle)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($fila['tema']); ?></td>
                    <td><?php echo htmlspecialchars($fila['enunciado']); ?></td>
                    <td><?php echo htmlspecialchars($fila['respuesta_estudiante']); ?></td>
                    <td><?php echo htmlspecialchars($fila['respuesta_correcta']); ?></td>
                    <td>
                        <?php if ($fila['es_correcta']) { ?>
                            Correcta
                        <?php } else { ?>
                            Incorrecta
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</section>

<?php include 'includes/footer.php'; ?>
