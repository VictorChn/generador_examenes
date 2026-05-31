<?php
include 'config/conexion.php';

if (!isset($_GET['id'])) {
    header("Location: preguntas.php");
    exit;
}

$id_pregunta = $_GET['id'];

$sql = "DELETE FROM preguntas WHERE id_pregunta = ?";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_pregunta);
mysqli_stmt_execute($stmt);

header("Location: preguntas.php");
exit;
?>
