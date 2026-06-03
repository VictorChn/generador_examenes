<?php
include 'config/conexion.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id_resultado = $_GET['id'];

// Obtener los datos principales del resultado
$sql_resultado = "
    SELECT
        resultados.*,
        examenes.titulo,
        examenes.fecha_creacion
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

<<<<<<< HEAD
// Iniciar sesión si no está iniciada para comprobar rol
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$es_profesor = isset($_SESSION['profesor_id']);

if ($es_profesor) {
    // ==========================================
    // VISTA DEL DOCENTE (Detalle completo)
    // ==========================================
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
        <p class="subtitulo">Aquí están tus resultados.</p>
    </section>

    <section class="tarjeta">
        <div class="grid">
            <div>
                <span class="subtitulo">Alumno</span>
                <h2><?php echo htmlspecialchars($resultado['nombre_estudiante']); ?></h2>
            </div>
            <div>
                <span class="subtitulo">Código del examen</span>
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
            <h2>Calificación</h2>
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
                <a class="boton boton-secundario" href="dashboard.php">Volver al inicio</a>
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
                    <th>Respuesta del alumno</th>
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
                                <span class="badge badge-verde">Correcta</span>
                            <?php } else { ?>
                                <span class="badge badge-rojo">Incorrecta</span>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </section>

    <?php 
    include 'includes/footer.php';

} else {
    // ==========================================
    // VISTA DEL ALUMNO (Tarjeta simplificada)
    // ==========================================
    $meses = ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];
    $mes_index = intval(date('n', strtotime($resultado['fecha_presentacion']))) - 1;
    $fecha_formato = date('d', strtotime($resultado['fecha_presentacion'])) . ' de ' . $meses[$mes_index] . ' de ' . date('Y', strtotime($resultado['fecha_presentacion']));

    $codigo_display = 'EX-' . date('Y', strtotime($resultado['fecha_creacion'] ?? date('Y-m-d'))) . '-' . str_pad($resultado['id_examen'], 3, '0', STR_PAD_LEFT);
    $calificacion_escala_10 = number_format($resultado['calificacion'] / 10, 1);
    $errores = $resultado['total_preguntas'] - $resultado['respuestas_correctas'];
    $porcentaje = number_format($resultado['calificacion'], 0);
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Resultados del Examen</title>
        <style>
            body {
                background-color: #f8fafc;
                margin: 0;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                color: #1e293b;
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 40px 20px;
            }

            .logo-header {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 15px;
                margin-bottom: 40px;
            }
            
            .logo-header img {
                height: 60px;
                width: auto;
            }
            
            .logo-text {
                text-align: left;
                line-height: 1.2;
            }
            
            .logo-text strong {
                color: #0f172a;
                font-size: 20px;
                font-weight: 800;
                letter-spacing: 1px;
            }
            
            .logo-text span {
                color: #d97706;
                font-size: 10px;
                font-weight: bold;
                letter-spacing: 2px;
                display: block;
                margin-top: 2px;
            }

            .success-icon {
                width: 56px;
                height: 56px;
                background: #22c55e;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
                color: #fff;
                box-shadow: 0 10px 20px rgba(34, 197, 94, 0.2);
            }

            .title {
                font-size: 28px;
                font-weight: bold;
                color: #0f172a;
                margin: 0 0 8px 0;
                text-align: center;
            }

            .subtitle {
                font-size: 15px;
                color: #64748b;
                text-align: center;
                margin-bottom: 40px;
            }

            .card {
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02), 0 10px 15px rgba(0, 0, 0, 0.03);
                width: 100%;
                max-width: 800px;
                margin-bottom: 24px;
                border: 1px solid #f1f5f9;
            }

            .info-row {
                display: flex;
                justify-content: space-between;
                padding: 24px 32px;
                gap: 20px;
            }

            .info-item {
                display: flex;
                align-items: center;
                gap: 16px;
                flex: 1;
            }

            .info-item:not(:last-child) {
                border-right: 1px solid #e2e8f0;
            }

            .info-icon {
                width: 40px;
                height: 40px;
                background: #eff6ff;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #3b82f6;
            }

            .info-text {
                display: flex;
                flex-direction: column;
            }

            .info-label {
                font-size: 12px;
                color: #64748b;
                margin-bottom: 4px;
            }

            .info-val {
                font-size: 15px;
                font-weight: 600;
                color: #0f172a;
            }

            .results-grid {
                display: grid;
                grid-template-columns: 280px 1fr;
                min-height: 280px;
            }

            .score-section {
                padding: 32px;
                border-right: 1px solid #e2e8f0;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }

            .score-title {
                font-size: 14px;
                font-weight: 600;
                color: #475569;
                margin-bottom: 24px;
            }

            .score-circle {
                width: 140px;
                height: 140px;
                border-radius: 50%;
                border: 8px solid #4ade80;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                margin-bottom: 24px;
                position: relative;
            }

            .score-circle::after {
                content: '';
                position: absolute;
                top: -8px;
                left: -8px;
                width: 140px;
                height: 140px;
                border-radius: 50%;
                border: 8px solid transparent;
                border-top-color: #f1f5f9;
                transform: rotate(-45deg);
            }

            .score-number {
                font-size: 42px;
                font-weight: bold;
                color: #1e293b;
                line-height: 1;
                z-index: 1;
            }
            
            .score-base {
                font-size: 14px;
                color: #64748b;
                z-index: 1;
            }

            .badge-excellent {
                background: #f0fdf4;
                color: #16a34a;
                padding: 6px 16px;
                border-radius: 6px;
                font-size: 13px;
                font-weight: 600;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                border: 1px solid #bbf7d0;
            }

            .details-section {
                padding: 32px;
            }
            
            .details-title {
                font-size: 14px;
                font-weight: 600;
                color: #475569;
                margin-bottom: 24px;
            }

            .details-list {
                list-style: none;
                padding: 0;
                margin: 0;
                display: flex;
                flex-direction: column;
                gap: 16px;
            }

            .details-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding-bottom: 16px;
                border-bottom: 1px dashed #e2e8f0;
            }
            
            .details-item:last-child {
                border-bottom: none;
                padding-bottom: 0;
            }

            .item-label {
                display: flex;
                align-items: center;
                gap: 12px;
                font-size: 15px;
                color: #475569;
            }
            
            .item-icon {
                width: 24px;
                height: 24px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .item-icon.gray { color: #16a34a; background: #fff; border: 1px solid #16a34a; }
            .item-icon.green { color: #16a34a; background: #fff; border: 1px solid #16a34a; }
            .item-icon.red { color: #dc2626; background: #fff; border: 1px solid #dc2626; }
            .item-icon.blue { color: #2563eb; background: #fff; border: 1px solid #2563eb; }

            .item-val {
                font-size: 16px;
                font-weight: bold;
                color: #0f172a;
            }
            
            .item-val.green { color: #16a34a; }
            .item-val.red { color: #dc2626; }
            .item-val.blue { color: #2563eb; }

            .btn-container {
                width: 100%;
                max-width: 800px;
                display: flex;
                justify-content: center;
                margin-top: 10px;
            }

            .btn-finish {
                background: #0f172a;
                color: #fff;
                text-decoration: none;
                padding: 14px 40px;
                border-radius: 8px;
                font-size: 15px;
                font-weight: 600;
                display: inline-flex;
                align-items: center;
                gap: 10px;
                transition: all 0.2s;
                min-width: 280px;
                justify-content: center;
            }

            .btn-finish:hover {
                background: #1e293b;
            }
            
            @media (max-width: 768px) {
                .info-row {
                    flex-direction: column;
                    gap: 16px;
                }
                .info-item:not(:last-child) {
                    border-right: none;
                    border-bottom: 1px solid #e2e8f0;
                    padding-bottom: 16px;
                }
                .results-grid {
                    grid-template-columns: 1fr;
                }
                .score-section {
                    border-right: none;
                    border-bottom: 1px solid #e2e8f0;
                }
            }
        </style>
    </head>
    <body>

        <div class="logo-header">
            <img src="imagenes/Logo.png" alt="Logo">
            <div class="logo-text">
                <strong>INSTITUTO<br>EDUCATIVO</strong>
                <span>FORMANDO FUTURO</span>
            </div>
        </div>

        <div class="success-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>

        <h1 class="title">¡Examen finalizado!</h1>
        <div class="subtitle">Aquí están tus resultados</div>

        <!-- Student Info Card -->
        <div class="card">
            <div class="info-row">
                <div class="info-item">
                    <div class="info-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </div>
                    <div class="info-text">
                        <span class="info-label">Alumno</span>
                        <span class="info-val"><?php echo htmlspecialchars($resultado['nombre_estudiante']); ?></span>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    </div>
                    <div class="info-text">
                        <span class="info-label">Código del Examen</span>
                        <span class="info-val"><?php echo htmlspecialchars($codigo_display); ?></span>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    </div>
                    <div class="info-text">
                        <span class="info-label">Fecha</span>
                        <span class="info-val"><?php echo $fecha_formato; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Card -->
        <div class="card results-grid">
            <div class="score-section">
                <div class="score-title">Tu calificación</div>
                
                <div class="score-circle">
                    <div class="score-number"><?php echo $calificacion_escala_10; ?></div>
                    <div class="score-base">de 10</div>
                </div>
                
                <?php if ($calificacion_escala_10 >= 8) { ?>
                    <div class="badge-excellent">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                        ¡Excelente trabajo!
                    </div>
                <?php } elseif ($calificacion_escala_10 >= 6) { ?>
                    <div class="badge-excellent" style="color: #d97706; background: #fef3c7; border-color: #fde68a;">
                        ¡Buen trabajo!
                    </div>
                <?php } else { ?>
                    <div class="badge-excellent" style="color: #dc2626; background: #fef2f2; border-color: #fecaca;">
                        Sigue estudiando
                    </div>
                <?php } ?>
            </div>
            
            <div class="details-section">
                <div class="details-title">Resumen de resultados</div>
                
                <ul class="details-list">
                    <li class="details-item">
                        <div class="item-label">
                            <div class="item-icon gray">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </div>
                            Total de preguntas
                        </div>
                        <div class="item-val"><?php echo $resultado['total_preguntas']; ?></div>
                    </li>
                    
                    <li class="details-item">
                        <div class="item-label">
                            <div class="item-icon green">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </div>
                            Total de aciertos
                        </div>
                        <div class="item-val green"><?php echo $resultado['respuestas_correctas']; ?></div>
                    </li>
                    
                    <li class="details-item">
                        <div class="item-label">
                            <div class="item-icon red">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            </div>
                            Total de errores
                        </div>
                        <div class="item-val red"><?php echo $errores; ?></div>
                    </li>
                    
                    <li class="details-item">
                        <div class="item-label">
                            <div class="item-icon blue">
                                <span style="font-weight: bold; font-size: 14px;">%</span>
                            </div>
                            Porcentaje obtenido
                        </div>
                        <div class="item-val blue"><?php echo $porcentaje; ?>%</div>
                    </li>
                </ul>
            </div>
        </div>

        <div class="btn-container">
            <a href="index.php" class="btn-finish">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                Finalizar y salir
            </a>
        </div>

    </body>
    </html>
    <?php
}
?>
=======
$meses = ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];
$mes_index = intval(date('n', strtotime($resultado['fecha_presentacion']))) - 1;
$fecha_formato = date('d', strtotime($resultado['fecha_presentacion'])) . ' de ' . $meses[$mes_index] . ' de ' . date('Y', strtotime($resultado['fecha_presentacion']));

$codigo_display = 'EX-' . date('Y', strtotime($resultado['fecha_creacion'] ?? date('Y-m-d'))) . '-' . str_pad($resultado['id_examen'], 3, '0', STR_PAD_LEFT);
$calificacion_escala_10 = number_format($resultado['calificacion'] / 10, 1);
$errores = $resultado['total_preguntas'] - $resultado['respuestas_correctas'];
$porcentaje = number_format($resultado['calificacion'], 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados del Examen</title>
    <style>
        body {
            background-color: #f8fafc;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #1e293b;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
        }

        .logo-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 40px;
        }
        
        .logo-header img {
            height: 60px;
            width: auto;
        }
        
        .logo-text {
            text-align: left;
            line-height: 1.2;
        }
        
        .logo-text strong {
            color: #0f172a;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 1px;
        }
        
        .logo-text span {
            color: #d97706;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 2px;
            display: block;
            margin-top: 2px;
        }

        .success-icon {
            width: 56px;
            height: 56px;
            background: #22c55e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: #fff;
            box-shadow: 0 10px 20px rgba(34, 197, 94, 0.2);
        }

        .title {
            font-size: 28px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 8px 0;
            text-align: center;
        }

        .subtitle {
            font-size: 15px;
            color: #64748b;
            text-align: center;
            margin-bottom: 40px;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02), 0 10px 15px rgba(0, 0, 0, 0.03);
            width: 100%;
            max-width: 800px;
            margin-bottom: 24px;
            border: 1px solid #f1f5f9;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 24px 32px;
            gap: 20px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 1;
        }

        .info-item:not(:last-child) {
            border-right: 1px solid #e2e8f0;
        }

        .info-icon {
            width: 40px;
            height: 40px;
            background: #eff6ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3b82f6;
        }

        .info-text {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 4px;
        }

        .info-val {
            font-size: 15px;
            font-weight: 600;
            color: #0f172a;
        }

        .results-grid {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 280px;
        }

        .score-section {
            padding: 32px;
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .score-title {
            font-size: 14px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 24px;
        }

        .score-circle {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            border: 8px solid #4ade80;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            position: relative;
        }

        /* Simulating progress gap */
        .score-circle::after {
            content: '';
            position: absolute;
            top: -8px;
            left: -8px;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            border: 8px solid transparent;
            border-top-color: #f1f5f9;
            transform: rotate(-45deg);
        }

        .score-number {
            font-size: 42px;
            font-weight: bold;
            color: #1e293b;
            line-height: 1;
            z-index: 1;
        }
        
        .score-base {
            font-size: 14px;
            color: #64748b;
            z-index: 1;
        }

        .badge-excellent {
            background: #f0fdf4;
            color: #16a34a;
            padding: 6px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #bbf7d0;
        }

        .details-section {
            padding: 32px;
        }
        
        .details-title {
            font-size: 14px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 24px;
        }

        .details-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .details-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 16px;
            border-bottom: 1px dashed #e2e8f0;
        }
        
        .details-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .item-label {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 15px;
            color: #475569;
        }
        
        .item-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .item-icon.gray { color: #16a34a; background: #fff; border: 1px solid #16a34a; }
        .item-icon.green { color: #16a34a; background: #fff; border: 1px solid #16a34a; }
        .item-icon.red { color: #dc2626; background: #fff; border: 1px solid #dc2626; }
        .item-icon.blue { color: #2563eb; background: #fff; border: 1px solid #2563eb; }

        .item-val {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
        }
        
        .item-val.green { color: #16a34a; }
        .item-val.red { color: #dc2626; }
        .item-val.blue { color: #2563eb; }

        .btn-container {
            width: 100%;
            max-width: 800px;
            display: flex;
            justify-content: center;
            margin-top: 10px;
        }

        .btn-finish {
            background: #0f172a;
            color: #fff;
            text-decoration: none;
            padding: 14px 40px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
            min-width: 280px;
            justify-content: center;
        }

        .btn-finish:hover {
            background: #1e293b;
        }
        
        @media (max-width: 768px) {
            .info-row {
                flex-direction: column;
                gap: 16px;
            }
            .info-item:not(:last-child) {
                border-right: none;
                border-bottom: 1px solid #e2e8f0;
                padding-bottom: 16px;
            }
            .results-grid {
                grid-template-columns: 1fr;
            }
            .score-section {
                border-right: none;
                border-bottom: 1px solid #e2e8f0;
            }
        }
    </style>
</head>
<body>

    <div class="logo-header">
        <img src="imagenes/Logo.png" alt="Logo">
        <div class="logo-text">
            <strong>INSTITUTO<br>EDUCATIVO</strong>
            <span>FORMANDO FUTURO</span>
        </div>
    </div>

    <div class="success-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
    </div>

    <h1 class="title">¡Examen finalizado!</h1>
    <div class="subtitle">Aquí están tus resultados</div>

    <!-- Student Info Card -->
    <div class="card">
        <div class="info-row">
            <div class="info-item">
                <div class="info-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </div>
                <div class="info-text">
                    <span class="info-label">Alumno</span>
                    <span class="info-val"><?php echo htmlspecialchars($resultado['nombre_estudiante']); ?></span>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                </div>
                <div class="info-text">
                    <span class="info-label">Código del Examen</span>
                    <span class="info-val"><?php echo htmlspecialchars($codigo_display); ?></span>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                </div>
                <div class="info-text">
                    <span class="info-label">Fecha</span>
                    <span class="info-val"><?php echo $fecha_formato; ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Card -->
    <div class="card results-grid">
        <div class="score-section">
            <div class="score-title">Tu calificación</div>
            
            <div class="score-circle">
                <div class="score-number"><?php echo $calificacion_escala_10; ?></div>
                <div class="score-base">de 10</div>
            </div>
            
            <?php if ($calificacion_escala_10 >= 8) { ?>
                <div class="badge-excellent">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                    ¡Excelente trabajo!
                </div>
            <?php } elseif ($calificacion_escala_10 >= 6) { ?>
                <div class="badge-excellent" style="color: #d97706; background: #fef3c7; border-color: #fde68a;">
                    ¡Buen trabajo!
                </div>
            <?php } else { ?>
                <div class="badge-excellent" style="color: #dc2626; background: #fef2f2; border-color: #fecaca;">
                    Sigue estudiando
                </div>
            <?php } ?>
        </div>
        
        <div class="details-section">
            <div class="details-title">Resumen de resultados</div>
            
            <ul class="details-list">
                <li class="details-item">
                    <div class="item-label">
                        <div class="item-icon gray">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </div>
                        Total de preguntas
                    </div>
                    <div class="item-val"><?php echo $resultado['total_preguntas']; ?></div>
                </li>
                
                <li class="details-item">
                    <div class="item-label">
                        <div class="item-icon green">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </div>
                        Total de aciertos
                    </div>
                    <div class="item-val green"><?php echo $resultado['respuestas_correctas']; ?></div>
                </li>
                
                <li class="details-item">
                    <div class="item-label">
                        <div class="item-icon red">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </div>
                        Total de errores
                    </div>
                    <div class="item-val red"><?php echo $errores; ?></div>
                </li>
                
                <li class="details-item">
                    <div class="item-label">
                        <div class="item-icon blue">
                            <span style="font-weight: bold; font-size: 14px;">%</span>
                        </div>
                        Porcentaje obtenido
                    </div>
                    <div class="item-val blue"><?php echo $porcentaje; ?>%</div>
                </li>
            </ul>
        </div>
    </div>

    <div class="btn-container">
        <a href="index.php" class="btn-finish">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
            Finalizar y salir
        </a>
    </div>

</body>
</html>
>>>>>>> b0ce2526d8b7fbcd61a207990251e08376002d93
