<?php
include 'config/conexion.php';

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
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
    header("Location: dashboard.php");
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado en PDF</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        body {
            background: #fff;
        }

        .barra-impresion {
            padding: 16px;
            background: #f4f6f8;
            border-bottom: 1px solid #d9e0e6;
        }

        .documento {
            max-width: 900px;
            margin: 24px auto;
            padding: 20px;
        }

        @media print {
            .barra-impresion {
                display: none;
            }

            .documento {
                margin: 0;
                max-width: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="barra-impresion">
        <button onclick="window.print();">Guardar o imprimir PDF</button>
        <a class="boton boton-secundario" href="resultado.php?id=<?php echo $resultado['id_resultado']; ?>">Volver al resultado</a>
    </div>

    <main class="documento">
        <h1>Resultado del examen</h1>

        <section class="tarjeta">
            <p><strong>Examen:</strong> <?php echo htmlspecialchars($resultado['titulo']); ?></p>
            <p><strong>Estudiante:</strong> <?php echo htmlspecialchars($resultado['nombre_estudiante']); ?></p>
            <p><strong>Fecha:</strong> <?php echo $resultado['fecha_presentacion']; ?></p>
            <p><strong>Correctas:</strong> <?php echo $resultado['respuestas_correctas']; ?> de <?php echo $resultado['total_preguntas']; ?></p>
            <p><strong>Calificacion:</strong> <?php echo number_format($resultado['calificacion'], 2); ?> / 100</p>
        </section>

        <section class="tarjeta">
            <h2>Detalle de respuestas</h2>

            <table>
                <thead>
                    <tr>
                        <th>Tema</th>
                        <th>Pregunta</th>
                        <th>Respuesta del estudiante</th>
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
    </main>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
