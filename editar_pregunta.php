<?php
include 'config/conexion.php';

$mensaje = "";

if (!isset($_GET['id'])) {
    header("Location: preguntas.php");
    exit;
}

$id_pregunta = $_GET['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_tema = $_POST['id_tema'];
    $tipo = $_POST['tipo'];
    $enunciado = trim($_POST['enunciado']);
    $opcion_a = trim($_POST['opcion_a']);
    $opcion_b = trim($_POST['opcion_b']);
    $opcion_c = trim($_POST['opcion_c']);
    $opcion_d = trim($_POST['opcion_d']);
    $respuesta_correcta = $_POST['respuesta_correcta'];

    if ($tipo == "verdadero_falso") {
        $opcion_a = "Verdadero";
        $opcion_b = "Falso";
        $opcion_c = null;
        $opcion_d = null;
    }

    if ($id_tema == "" || $tipo == "" || $enunciado == "" || $respuesta_correcta == "") {
        $mensaje = "<div class='mensaje mensaje-error'>Completa los campos obligatorios.</div>";
    } else {
        $sql = "UPDATE preguntas
                SET id_tema = ?,
                    tipo = ?,
                    enunciado = ?,
                    opcion_a = ?,
                    opcion_b = ?,
                    opcion_c = ?,
                    opcion_d = ?,
                    respuesta_correcta = ?
                WHERE id_pregunta = ?";

        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param(
            $stmt,
            "isssssssi",
            $id_tema,
            $tipo,
            $enunciado,
            $opcion_a,
            $opcion_b,
            $opcion_c,
            $opcion_d,
            $respuesta_correcta,
            $id_pregunta
        );

        if (mysqli_stmt_execute($stmt)) {
            $mensaje = "<div class='mensaje mensaje-exito'>Pregunta actualizada correctamente.</div>";
        } else {
            $mensaje = "<div class='mensaje mensaje-error'>Error al actualizar la pregunta.</div>";
        }
    }
}

$stmt_pregunta = mysqli_prepare($conexion, "SELECT * FROM preguntas WHERE id_pregunta = ?");
mysqli_stmt_bind_param($stmt_pregunta, "i", $id_pregunta);
mysqli_stmt_execute($stmt_pregunta);
$resultado_pregunta = mysqli_stmt_get_result($stmt_pregunta);
$pregunta = mysqli_fetch_assoc($resultado_pregunta);

if (!$pregunta) {
    header("Location: preguntas.php");
    exit;
}

$consulta_temas = mysqli_query($conexion, "SELECT * FROM temas ORDER BY nombre ASC");

include 'includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="subtitulo">Banco de preguntas</p>
        <h1>Editar pregunta</h1>
    </div>
    <a class="boton boton-secundario" href="preguntas.php">Volver al banco</a>
</div>

<?php echo $mensaje; ?>

<form method="POST" action="editar_pregunta.php?id=<?php echo $pregunta['id_pregunta']; ?>">
    <h2>1. Informacion general</h2>
    <div class="grid">
        <div>
            <label for="id_tema">Tema</label>
            <select name="id_tema" id="id_tema" required>
                <option value="">Selecciona un tema</option>
                <?php while ($tema = mysqli_fetch_assoc($consulta_temas)) { ?>
                    <option value="<?php echo $tema['id_tema']; ?>" <?php if ($tema['id_tema'] == $pregunta['id_tema']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($tema['nombre']); ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div>
            <label for="tipo">Tipo de pregunta</label>
            <select name="tipo" id="tipo" onchange="mostrarOpcionesPregunta();" required>
                <option value="">Selecciona el tipo</option>
                <option value="opcion_multiple" <?php if ($pregunta['tipo'] == 'opcion_multiple') echo 'selected'; ?>>Opcion multiple</option>
                <option value="verdadero_falso" <?php if ($pregunta['tipo'] == 'verdadero_falso') echo 'selected'; ?>>Verdadero/Falso</option>
            </select>
        </div>
    </div>

    <label for="enunciado">Pregunta</label>
    <textarea name="enunciado" id="enunciado" required><?php echo htmlspecialchars($pregunta['enunciado']); ?></textarea>

    <h2>2. Opciones de respuesta</h2>
    <div id="opciones_multiple">
        <div class="grid">
            <div>
                <label for="opcion_a">Opcion A</label>
                <input type="text" name="opcion_a" id="opcion_a" value="<?php echo htmlspecialchars($pregunta['opcion_a']); ?>">
            </div>
            <div>
                <label for="opcion_b">Opcion B</label>
                <input type="text" name="opcion_b" id="opcion_b" value="<?php echo htmlspecialchars($pregunta['opcion_b']); ?>">
            </div>
            <div>
                <label for="opcion_c">Opcion C</label>
                <input type="text" name="opcion_c" id="opcion_c" value="<?php echo htmlspecialchars($pregunta['opcion_c']); ?>">
            </div>
            <div>
                <label for="opcion_d">Opcion D</label>
                <input type="text" name="opcion_d" id="opcion_d" value="<?php echo htmlspecialchars($pregunta['opcion_d']); ?>">
            </div>
        </div>
    </div>

    <h2>3. Respuesta correcta</h2>
    <label for="respuesta_correcta">Respuesta correcta</label>
    <select name="respuesta_correcta" id="respuesta_correcta" data-valor="<?php echo htmlspecialchars($pregunta['respuesta_correcta']); ?>" required>
        <option value="">Selecciona la respuesta</option>
        <option value="A" <?php if ($pregunta['respuesta_correcta'] == 'A') echo 'selected'; ?>>A</option>
        <option value="B" <?php if ($pregunta['respuesta_correcta'] == 'B') echo 'selected'; ?>>B</option>
        <option value="C" <?php if ($pregunta['respuesta_correcta'] == 'C') echo 'selected'; ?>>C</option>
        <option value="D" <?php if ($pregunta['respuesta_correcta'] == 'D') echo 'selected'; ?>>D</option>
        <option value="Verdadero" <?php if ($pregunta['respuesta_correcta'] == 'Verdadero') echo 'selected'; ?>>Verdadero</option>
        <option value="Falso" <?php if ($pregunta['respuesta_correcta'] == 'Falso') echo 'selected'; ?>>Falso</option>
    </select>

    <div class="acciones">
        <input type="submit" value="Actualizar pregunta">
        <a class="boton boton-secundario" href="preguntas.php">Cancelar</a>
    </div>
</form>

<?php include 'includes/footer.php'; ?>
