<?php
include 'config/conexion.php';

if (!isset($_GET['id'])) {
    header("Location: generar_examen.php");
    exit;
}

$id_examen = $_GET['id'];

$stmt_examen = mysqli_prepare($conexion, "SELECT * FROM examenes WHERE id_examen = ?");
mysqli_stmt_bind_param($stmt_examen, "i", $id_examen);
mysqli_stmt_execute($stmt_examen);
$resultado_examen = mysqli_stmt_get_result($stmt_examen);
$examen = mysqli_fetch_assoc($resultado_examen);

if (!$examen) {
    header("Location: generar_examen.php");
    exit;
}

$sql_preguntas = "
    SELECT preguntas.*
    FROM examen_preguntas
    INNER JOIN preguntas ON examen_preguntas.id_pregunta = preguntas.id_pregunta
    WHERE examen_preguntas.id_examen = ?
";

$stmt_preguntas = mysqli_prepare($conexion, $sql_preguntas);
mysqli_stmt_bind_param($stmt_preguntas, "i", $id_examen);
mysqli_stmt_execute($stmt_preguntas);
$resultado_preguntas = mysqli_stmt_get_result($stmt_preguntas);

include 'includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Examen: <?php echo htmlspecialchars($examen['titulo']); ?></h1>
        <p>Codigo: EX-<?php echo str_pad($examen['id_examen'], 4, '0', STR_PAD_LEFT); ?></p>
    </div>
    <a class="boton boton-peligro" href="generar_examen.php">Finalizar examen</a>
</div>

<form class="form-limpio" method="POST" action="calificar.php">
    <input type="hidden" name="id_examen" value="<?php echo $examen['id_examen']; ?>">

    <section class="tarjeta">
        <label for="nombre_estudiante">Nombre del estudiante</label>
        <input type="text" name="nombre_estudiante" id="nombre_estudiante" required>
    </section>

    <div class="exam-shell">
        <section>
            <?php
            $numero = 1;
            while ($pregunta = mysqli_fetch_assoc($resultado_preguntas)) {
            ?>
                <article class="tarjeta">
                    <span class="badge badge-azul">Pregunta <?php echo $numero; ?></span>
                    <h2><?php echo htmlspecialchars($pregunta['enunciado']); ?></h2>

                    <?php if ($pregunta['tipo'] == 'opcion_multiple') { ?>
                        <label class="opcion-respuesta">
                            <input type="radio" name="respuestas[<?php echo $pregunta['id_pregunta']; ?>]" value="A" required>
                            A. <?php echo htmlspecialchars($pregunta['opcion_a']); ?>
                        </label>

                        <label class="opcion-respuesta">
                            <input type="radio" name="respuestas[<?php echo $pregunta['id_pregunta']; ?>]" value="B">
                            B. <?php echo htmlspecialchars($pregunta['opcion_b']); ?>
                        </label>

                        <label class="opcion-respuesta">
                            <input type="radio" name="respuestas[<?php echo $pregunta['id_pregunta']; ?>]" value="C">
                            C. <?php echo htmlspecialchars($pregunta['opcion_c']); ?>
                        </label>

                        <label class="opcion-respuesta">
                            <input type="radio" name="respuestas[<?php echo $pregunta['id_pregunta']; ?>]" value="D">
                            D. <?php echo htmlspecialchars($pregunta['opcion_d']); ?>
                        </label>
                    <?php } else { ?>
                        <label class="opcion-respuesta">
                            <input type="radio" name="respuestas[<?php echo $pregunta['id_pregunta']; ?>]" value="Verdadero" required>
                            Verdadero
                        </label>

                        <label class="opcion-respuesta">
                            <input type="radio" name="respuestas[<?php echo $pregunta['id_pregunta']; ?>]" value="Falso">
                            Falso
                        </label>
                    <?php } ?>
                </article>
            <?php
                $numero++;
            }
            ?>
        </section>

        <aside>
            <section class="tarjeta">
                <h2>Informacion del examen</h2>
                <p><strong>Total de preguntas:</strong> <?php echo $examen['total_preguntas']; ?></p>
                <p><strong>Tipo:</strong> Opcion multiple y verdadero/falso</p>
                <p><strong>Codigo:</strong> EX-<?php echo str_pad($examen['id_examen'], 4, '0', STR_PAD_LEFT); ?></p>
                <div class="mensaje mensaje-exito">Selecciona una respuesta por cada pregunta.</div>
            </section>
        </aside>
    </div>

    <div class="acciones">
        <input type="submit" value="Calificar examen">
        <a class="boton boton-secundario" href="generar_examen.php">Generar otro examen</a>
    </div>
</form>

<?php include 'includes/footer.php'; ?>
