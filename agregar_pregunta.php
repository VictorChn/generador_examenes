<?php
include 'config/conexion.php';

$mensaje = "";
$consulta_temas = mysqli_query($conexion, "SELECT * FROM temas ORDER BY nombre ASC");

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
        $sql = "INSERT INTO preguntas (
                    id_tema,
                    tipo,
                    enunciado,
                    opcion_a,
                    opcion_b,
                    opcion_c,
                    opcion_d,
                    respuesta_correcta
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param(
            $stmt,
            "isssssss",
            $id_tema,
            $tipo,
            $enunciado,
            $opcion_a,
            $opcion_b,
            $opcion_c,
            $opcion_d,
            $respuesta_correcta
        );

        if (mysqli_stmt_execute($stmt)) {
            $mensaje = "<div class='mensaje mensaje-exito'>Pregunta registrada correctamente.</div>";
        } else {
            $mensaje = "<div class='mensaje mensaje-error'>Error al registrar la pregunta.</div>";
        }
    }
}

include 'includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="subtitulo">Banco de preguntas</p>
        <h1>Agregar nueva pregunta</h1>
    </div>
    <a class="boton boton-secundario" href="preguntas.php">Volver al banco</a>
</div>

<?php echo $mensaje; ?>

<form method="POST" action="agregar_pregunta.php">
    <h2>1. Informacion general</h2>
    <div class="grid">
        <div>
            <label for="id_tema">Tema</label>
            <select name="id_tema" id="id_tema" required>
                <option value="">Selecciona un tema</option>
                <?php while ($tema = mysqli_fetch_assoc($consulta_temas)) { ?>
                    <option value="<?php echo $tema['id_tema']; ?>">
                        <?php echo htmlspecialchars($tema['nombre']); ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div>
            <label for="tipo">Tipo de pregunta</label>
            <select name="tipo" id="tipo" onchange="mostrarOpcionesPregunta();" required>
                <option value="">Selecciona el tipo</option>
                <option value="opcion_multiple">Opcion multiple</option>
                <option value="verdadero_falso">Verdadero/Falso</option>
            </select>
        </div>
    </div>

    <label for="enunciado">Pregunta</label>
    <textarea name="enunciado" id="enunciado" placeholder="Escribe aqui el enunciado de la pregunta..." required></textarea>

    <h2>2. Opciones de respuesta</h2>
    <div class="mensaje mensaje-exito">Marca o selecciona la respuesta correcta. Solo una opcion puede ser correcta.</div>

    <div id="opciones_multiple">
        <div class="grid">
            <div>
                <label for="opcion_a">Opcion A</label>
                <input type="text" name="opcion_a" id="opcion_a" placeholder="Escribe la opcion A">
            </div>

            <div>
                <label for="opcion_b">Opcion B</label>
                <input type="text" name="opcion_b" id="opcion_b" placeholder="Escribe la opcion B">
            </div>

            <div>
                <label for="opcion_c">Opcion C</label>
                <input type="text" name="opcion_c" id="opcion_c" placeholder="Escribe la opcion C">
            </div>

            <div>
                <label for="opcion_d">Opcion D</label>
                <input type="text" name="opcion_d" id="opcion_d" placeholder="Escribe la opcion D">
            </div>
        </div>
    </div>

    <h2>3. Respuesta correcta</h2>
    <select name="respuesta_correcta" id="respuesta_correcta" required>
        <option value="">Selecciona la respuesta</option>
        <option value="A">A</option>
        <option value="B">B</option>
        <option value="C">C</option>
        <option value="D">D</option>
        <option value="Verdadero">Verdadero</option>
        <option value="Falso">Falso</option>
    </select>

    <div class="acciones">
        <input type="submit" value="Guardar pregunta">
        <a class="boton boton-secundario" href="preguntas.php">Cancelar</a>
    </div>
</form>

<?php include 'includes/footer.php'; ?>
