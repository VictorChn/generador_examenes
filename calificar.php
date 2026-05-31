<?php
include 'config/conexion.php';

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: generar_examen.php");
    exit;
}

$id_examen = $_POST['id_examen'];
$nombre_estudiante = trim($_POST['nombre_estudiante']);
$respuestas = $_POST['respuestas'];

if ($nombre_estudiante == "" || empty($respuestas)) {
    header("Location: examen.php?id=" . $id_examen);
    exit;
}

$sql_preguntas = "
    SELECT preguntas.id_pregunta, preguntas.respuesta_correcta
    FROM examen_preguntas
    INNER JOIN preguntas ON examen_preguntas.id_pregunta = preguntas.id_pregunta
    WHERE examen_preguntas.id_examen = ?
";

$stmt_preguntas = mysqli_prepare($conexion, $sql_preguntas);
mysqli_stmt_bind_param($stmt_preguntas, "i", $id_examen);
mysqli_stmt_execute($stmt_preguntas);
$resultado_preguntas = mysqli_stmt_get_result($stmt_preguntas);

$total_preguntas = mysqli_num_rows($resultado_preguntas);
$respuestas_correctas = 0;
$detalle_respuestas = [];

while ($pregunta = mysqli_fetch_assoc($resultado_preguntas)) {
    $id_pregunta = $pregunta['id_pregunta'];
    $respuesta_correcta = $pregunta['respuesta_correcta'];
    $respuesta_estudiante = isset($respuestas[$id_pregunta]) ? $respuestas[$id_pregunta] : "";
    $es_correcta = ($respuesta_estudiante == $respuesta_correcta) ? 1 : 0;

    if ($es_correcta) {
        $respuestas_correctas++;
    }

    $detalle_respuestas[] = [
        'id_pregunta' => $id_pregunta,
        'respuesta_estudiante' => $respuesta_estudiante,
        'es_correcta' => $es_correcta
    ];
}

if ($total_preguntas == 0) {
    header("Location: generar_examen.php");
    exit;
}

$calificacion = ($respuestas_correctas / $total_preguntas) * 100;

$sql_resultado = "INSERT INTO resultados (
                    id_examen,
                    nombre_estudiante,
                    total_preguntas,
                    respuestas_correctas,
                    calificacion
                ) VALUES (?, ?, ?, ?, ?)";

$stmt_resultado = mysqli_prepare($conexion, $sql_resultado);
mysqli_stmt_bind_param(
    $stmt_resultado,
    "isiid",
    $id_examen,
    $nombre_estudiante,
    $total_preguntas,
    $respuestas_correctas,
    $calificacion
);
mysqli_stmt_execute($stmt_resultado);

$id_resultado = mysqli_insert_id($conexion);

foreach ($detalle_respuestas as $detalle) {
    $sql_respuesta = "INSERT INTO respuestas_estudiante (
                        id_resultado,
                        id_pregunta,
                        respuesta_estudiante,
                        es_correcta
                    ) VALUES (?, ?, ?, ?)";

    $stmt_respuesta = mysqli_prepare($conexion, $sql_respuesta);
    mysqli_stmt_bind_param(
        $stmt_respuesta,
        "iisi",
        $id_resultado,
        $detalle['id_pregunta'],
        $detalle['respuesta_estudiante'],
        $detalle['es_correcta']
    );
    mysqli_stmt_execute($stmt_respuesta);
}

header("Location: resultado.php?id=" . $id_resultado);
exit;
?>
