<?php
include 'config/conexion.php';
include 'includes/header.php';

$consulta = "
    SELECT
        preguntas.id_pregunta,
        preguntas.tipo,
        preguntas.enunciado,
        preguntas.respuesta_correcta,
        preguntas.fecha_creacion,
        temas.nombre AS tema
    FROM preguntas
    INNER JOIN temas ON preguntas.id_tema = temas.id_tema
    ORDER BY preguntas.id_pregunta DESC
";

$resultado = mysqli_query($conexion, $consulta);
?>

<div class="page-head">
    <div>
        <h1>Banco de preguntas</h1>
        <p>Consulta, administra y clasifica las preguntas registradas.</p>
    </div>
    <a class="boton" href="agregar_pregunta.php">+ Agregar nueva pregunta</a>
</div>

<section class="tarjeta">
    <h2>Preguntas registradas</h2>

    <?php if ($resultado && mysqli_num_rows($resultado) > 0) { ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tema</th>
                    <th>Tipo</th>
                    <th>Pregunta</th>
                    <th>Respuesta correcta</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($pregunta = mysqli_fetch_assoc($resultado)) { ?>
                    <tr>
                        <td><?php echo $pregunta['id_pregunta']; ?></td>
                        <td><?php echo htmlspecialchars($pregunta['tema']); ?></td>
                        <td>
                            <?php
                            if ($pregunta['tipo'] == 'opcion_multiple') {
                                echo '<span class="badge badge-azul">Opcion multiple</span>';
                            } else {
                                echo '<span class="badge badge-amarillo">Verdadero/Falso</span>';
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($pregunta['enunciado']); ?></td>
                        <td><?php echo htmlspecialchars($pregunta['respuesta_correcta']); ?></td>
                        <td>
                            <div class="acciones">
                                <a class="boton boton-secundario" href="editar_pregunta.php?id=<?php echo $pregunta['id_pregunta']; ?>">Editar</a>
                                <a class="boton boton-peligro" href="eliminar_pregunta.php?id=<?php echo $pregunta['id_pregunta']; ?>" onclick="return confirmarEliminacion();">Eliminar</a>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <p>No hay preguntas registradas.</p>
    <?php } ?>
</section>

<?php include 'includes/footer.php'; ?>
