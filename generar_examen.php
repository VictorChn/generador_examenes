<?php
include 'config/conexion.php';

$mensaje = "";
$consulta_temas = mysqli_query($conexion, "SELECT * FROM temas ORDER BY nombre ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo = trim($_POST['titulo']);
    $id_tema = $_POST['id_tema'];
    $total_preguntas = (int) $_POST['total_preguntas'];

    if ($titulo == "" || $id_tema == "" || $total_preguntas <= 0) {
        $mensaje = "<div class='mensaje mensaje-error'>Completa todos los campos correctamente.</div>";
    } else {
        $sql_preguntas = "SELECT id_pregunta
                          FROM preguntas
                          WHERE id_tema = ?
                          ORDER BY RAND()
                          LIMIT ?";

        $stmt_preguntas = mysqli_prepare($conexion, $sql_preguntas);
        mysqli_stmt_bind_param($stmt_preguntas, "ii", $id_tema, $total_preguntas);
        mysqli_stmt_execute($stmt_preguntas);
        $resultado_preguntas = mysqli_stmt_get_result($stmt_preguntas);

        if (mysqli_num_rows($resultado_preguntas) < $total_preguntas) {
            $mensaje = "<div class='mensaje mensaje-error'>No hay suficientes preguntas registradas para ese tema.</div>";
        } else {
            $sql_examen = "INSERT INTO examenes (titulo, total_preguntas) VALUES (?, ?)";
            $stmt_examen = mysqli_prepare($conexion, $sql_examen);
            mysqli_stmt_bind_param($stmt_examen, "si", $titulo, $total_preguntas);

            if (mysqli_stmt_execute($stmt_examen)) {
                $id_examen = mysqli_insert_id($conexion);

                while ($pregunta = mysqli_fetch_assoc($resultado_preguntas)) {
                    $sql_relacion = "INSERT INTO examen_preguntas (id_examen, id_pregunta) VALUES (?, ?)";
                    $stmt_relacion = mysqli_prepare($conexion, $sql_relacion);
                    mysqli_stmt_bind_param($stmt_relacion, "ii", $id_examen, $pregunta['id_pregunta']);
                    mysqli_stmt_execute($stmt_relacion);
                }

                header("Location: examen.php?id=" . $id_examen);
                exit;
            } else {
                $mensaje = "<div class='mensaje mensaje-error'>Error al generar el examen.</div>";
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Generar examen</h1>
        <p>Crea un nuevo examen seleccionando tema y cantidad de preguntas.</p>
    </div>
    <a class="boton boton-secundario" href="index.php">Historial de examenes</a>
</div>

<?php echo $mensaje; ?>

<form class="form-limpio" method="POST" action="generar_examen.php">
    <section class="tarjeta">
        <div class="grid">
            <div><span class="badge badge-azul">1 Configuracion</span></div>
            <div><span class="badge">2 Vista previa</span></div>
            <div><span class="badge">3 Generar</span></div>
        </div>
    </section>

    <div class="panel-grid">
        <section>
            <h2>1. Cantidad y tema</h2>

            <label for="titulo">Titulo del examen</label>
            <input type="text" name="titulo" id="titulo" placeholder="Ejemplo: Examen de Programacion Basica" required>

            <label for="id_tema">Tema</label>
            <select name="id_tema" id="id_tema" required>
                <option value="">Selecciona un tema</option>
                <?php while ($tema = mysqli_fetch_assoc($consulta_temas)) { ?>
                    <option value="<?php echo $tema['id_tema']; ?>">
                        <?php echo htmlspecialchars($tema['nombre']); ?>
                    </option>
                <?php } ?>
            </select>

            <label for="total_preguntas">Cantidad de preguntas</label>
            <input type="number" name="total_preguntas" id="total_preguntas" min="1" required>

            <div class="mensaje mensaje-exito">
                El sistema seleccionara aleatoriamente las preguntas disponibles del tema elegido.
            </div>
        </section>

        <aside class="tarjeta">
            <h2>Resumen de la generacion</h2>
            <div class="quick-list">
                <div class="quick-item"><span class="stat-icono stat-azul">#</span><span><strong>Cantidad de preguntas</strong><span>Definida por ti</span></span></div>
                <div class="quick-item"><span class="stat-icono stat-verde">?</span><span><strong>Tema seleccionado</strong><span>Desde tu banco</span></span></div>
                <div class="quick-item"><span class="stat-icono stat-amarillo">R</span><span><strong>Metodo de seleccion</strong><span>Aleatoria</span></span></div>
            </div>
        </aside>
    </div>

    <div class="acciones">
        <input type="submit" value="Generar examen">
        <a class="boton boton-secundario" href="index.php">Volver</a>
    </div>
</form>

<?php include 'includes/footer.php'; ?>
