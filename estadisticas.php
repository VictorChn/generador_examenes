<?php
include 'config/conexion.php';
include 'includes/header.php';

// Obtener la lista de todos los exámenes para el selector
$sql_examenes = "SELECT id_examen, titulo, fecha_creacion FROM examenes ORDER BY fecha_creacion DESC";
$consulta_examenes = mysqli_query($conexion, $sql_examenes);

// Determinar si hay un examen seleccionado
$id_examen_seleccionado = null;
if (isset($_GET['id_examen']) && $_GET['id_examen'] !== 'todos') {
    $id_examen_seleccionado = intval($_GET['id_examen']);
}

// Consultar estadísticas generales (Tarjetas superiores)
if ($id_examen_seleccionado) {
    $sql_general = "
        SELECT
            COUNT(id_resultado) AS examenes_contestados,
            AVG(calificacion) AS promedio_general,
            MAX(calificacion) AS mejor_calificacion,
            MIN(calificacion) AS menor_calificacion
        FROM resultados
        WHERE id_examen = ?
    ";
    $stmt_gen = mysqli_prepare($conexion, $sql_general);
    mysqli_stmt_bind_param($stmt_gen, "i", $id_examen_seleccionado);
    mysqli_stmt_execute($stmt_gen);
    $consulta_general = mysqli_stmt_get_result($stmt_gen);
    $general = mysqli_fetch_assoc($consulta_general);
} else {
    $sql_general = "
        SELECT
            COUNT(id_resultado) AS examenes_contestados,
            AVG(calificacion) AS promedio_general,
            MAX(calificacion) AS mejor_calificacion,
            MIN(calificacion) AS menor_calificacion
        FROM resultados
    ";
    $consulta_general = mysqli_query($conexion, $sql_general);
    $general = mysqli_fetch_assoc($consulta_general);
}

// Consultar desempeño por tema
if ($id_examen_seleccionado) {
    $sql_estadisticas = "
        SELECT
            temas.nombre AS tema,
            COUNT(respuestas_estudiante.id_respuesta) AS total_respuestas,
            SUM(respuestas_estudiante.es_correcta) AS respuestas_correctas,
            COUNT(respuestas_estudiante.id_respuesta) - SUM(respuestas_estudiante.es_correcta) AS respuestas_incorrectas,
            (SUM(respuestas_estudiante.es_correcta) / COUNT(respuestas_estudiante.id_respuesta)) * 100 AS porcentaje_acierto
        FROM respuestas_estudiante
        INNER JOIN resultados ON respuestas_estudiante.id_resultado = resultados.id_resultado
        INNER JOIN preguntas ON respuestas_estudiante.id_pregunta = preguntas.id_pregunta
        INNER JOIN temas ON preguntas.id_tema = temas.id_tema
        WHERE resultados.id_examen = ?
        GROUP BY temas.id_tema, temas.nombre
        ORDER BY porcentaje_acierto DESC
    ";
    $stmt_est = mysqli_prepare($conexion, $sql_estadisticas);
    mysqli_stmt_bind_param($stmt_est, "i", $id_examen_seleccionado);
    mysqli_stmt_execute($stmt_est);
    $estadisticas_res = mysqli_stmt_get_result($stmt_est);
} else {
    $sql_estadisticas = "
        SELECT
            temas.nombre AS tema,
            COUNT(respuestas_estudiante.id_respuesta) AS total_respuestas,
            SUM(respuestas_estudiante.es_correcta) AS respuestas_correctas,
            COUNT(respuestas_estudiante.id_respuesta) - SUM(respuestas_estudiante.es_correcta) AS respuestas_incorrectas,
            (SUM(respuestas_estudiante.es_correcta) / COUNT(respuestas_estudiante.id_respuesta)) * 100 AS porcentaje_acierto
        FROM respuestas_estudiante
        INNER JOIN preguntas ON respuestas_estudiante.id_pregunta = preguntas.id_pregunta
        INNER JOIN temas ON preguntas.id_tema = temas.id_tema
        GROUP BY temas.id_tema, temas.nombre
        ORDER BY porcentaje_acierto DESC
    ";
    $estadisticas_res = mysqli_query($conexion, $sql_estadisticas);
}

// Almacenar en array para procesar gráfica y listas de dominados vs repaso
$datos_temas = [];
$temas_dominados = [];
$temas_repaso = [];

if ($estadisticas_res) {
    while ($row = mysqli_fetch_assoc($estadisticas_res)) {
        $porcentaje = floatval($row['porcentaje_acierto']);
        $row['porcentaje_acierto'] = $porcentaje; // Asegurar que sea float
        $datos_temas[] = $row;
        
        if ($porcentaje >= 80) {
            $temas_dominados[] = $row;
        } else {
            $temas_repaso[] = $row;
        }
    }
}
?>

<style>
    /* Estilos del Selector Premium */
    .selector-container {
        background: #fff;
        border: 1px solid var(--linea);
        border-radius: 8px;
        padding: 16px 24px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.01);
    }

    .selector-container label {
        font-weight: 700;
        color: var(--texto);
        font-size: 14.5px;
    }

    .selector-container select {
        padding: 10px 16px;
        border: 1px solid var(--linea);
        border-radius: 6px;
        font-size: 14px;
        color: var(--texto);
        background-color: #fff;
        outline: none;
        min-width: 320px;
        cursor: pointer;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .selector-container select:focus {
        border-color: var(--azul-600);
        box-shadow: 0 0 0 3px rgba(0, 87, 255, 0.1);
    }

    /* Grilla de Paneles */
    .paneles-desempeno {
        display: grid;
        grid-template-columns: 1.2fr 0.8fr;
        gap: 24px;
        margin-bottom: 24px;
    }

    @media (max-width: 1100px) {
        .paneles-desempeno {
            grid-template-columns: 1fr;
        }
    }

    /* Gráfica de Barras CSS */
    .chart-box {
        display: flex;
        flex-direction: column;
        gap: 20px;
        padding: 8px 0;
    }

    .chart-row {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .chart-label-group {
        display: flex;
        justify-content: space-between;
        font-size: 13.5px;
        font-weight: 600;
        color: var(--texto);
    }

    .chart-bar-bg {
        background: #f1f5f9;
        height: 16px;
        border-radius: 8px;
        width: 100%;
        overflow: hidden;
        position: relative;
    }

    .chart-bar-fill {
        height: 100%;
        border-radius: 8px;
        transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        width: 0%; /* Animado por JS */
    }

    /* Gradientes para barras de rendimiento */
    .bar-verde {
        background: linear-gradient(90deg, #10b981, #059669);
    }

    .bar-amarilla {
        background: linear-gradient(90deg, #f59e0b, #d97706);
    }

    .bar-roja {
        background: linear-gradient(90deg, #ef4444, #dc2626);
    }

    /* Bloques de Dominados vs Repaso */
    .clasificacion-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-top: 8px;
    }

    @media (max-width: 768px) {
        .clasificacion-grid {
            grid-template-columns: 1fr;
        }
    }

    .bloque-categoria {
        border-radius: 12px;
        padding: 24px;
        border: 1px solid;
    }

    .bloque-dominado {
        background-color: #f0fdf4;
        border-color: #bbf7d0;
    }

    .bloque-repaso {
        background-color: #fef2f2;
        border-color: #fecaca;
    }

    .bloque-header {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 15.5px;
        font-weight: 700;
        margin-bottom: 16px;
    }

    .header-dominado {
        color: #15803d;
    }

    .header-repaso {
        color: #b91c1c;
    }

    .lista-categorias {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .lista-categorias li {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fff;
        padding: 12px 16px;
        border-radius: 8px;
        border: 1px solid;
        font-weight: 600;
        font-size: 13.5px;
    }

    .li-dominado {
        border-color: #dcfce7;
        color: #14532d;
    }

    .li-repaso {
        border-color: #fee2e2;
        color: #7f1d1d;
    }

    .badge-repaso-prioridad {
        padding: 4px 10px;
        border-radius: 99px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .pri-alta {
        background: #fee2e2;
        color: #ef4444;
    }

    .pri-media {
        background: #fef3c7;
        color: #d97706;
    }
</style>

<div class="page-head">
    <div>
        <h1>Estadísticas de Desempeño</h1>
        <p>Analiza el rendimiento por tema en los exámenes aplicados de forma dinámica.</p>
    </div>
    <a class="boton boton-secundario" href="javascript:window.print();">Exportar reporte</a>
</div>

<!-- Selector de Examen Específico -->
<div class="selector-container">
    <label for="id_examen">Seleccionar examen:</label>
    <select name="id_examen" id="selector-examen">
        <option value="todos">Todos los exámenes (Acumulado)</option>
        <?php 
        if ($consulta_examenes) {
            while ($exam = mysqli_fetch_assoc($consulta_examenes)) {
                $codigo_ex = 'EX-' . date('Y', strtotime($exam['fecha_creacion'])) . '-' . str_pad($exam['id_examen'], 3, '0', STR_PAD_LEFT);
                $selected = ($id_examen_seleccionado == $exam['id_examen']) ? 'selected' : '';
                echo '<option value="' . $exam['id_examen'] . '" ' . $selected . '>' . $codigo_ex . ' - ' . htmlspecialchars($exam['titulo']) . '</option>';
            }
        }
        ?>
    </select>
</div>

<!-- Tarjetas de Información General -->
<section class="stats-grid">
    <article class="tarjeta stat-card">
        <div class="stat-icono stat-azul">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
        </div>
        <div>
            <h3>Exámenes contestados</h3>
            <strong class="stat-numero"><?php echo $general['examenes_contestados'] ?? 0; ?></strong>
        </div>
    </article>

    <article class="tarjeta stat-card">
        <div class="stat-icono stat-verde">
            <span style="font-weight: 800; font-size: 18px;">%</span>
        </div>
        <div>
            <h3>Promedio general</h3>
            <strong class="stat-numero"><?php echo number_format($general['promedio_general'] ?? 0, 0); ?>%</strong>
        </div>
    </article>

    <article class="tarjeta stat-card">
        <div class="stat-icono stat-amarillo">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
        </div>
        <div>
            <h3>Mejor calificación</h3>
            <strong class="stat-numero"><?php echo number_format($general['mejor_calificacion'] ?? 0, 0); ?>%</strong>
        </div>
    </article>

    <article class="tarjeta stat-card">
        <div class="stat-icono stat-rojo">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
        </div>
        <div>
            <h3>Menor calificación</h3>
            <strong class="stat-numero"><?php echo number_format($general['menor_calificacion'] ?? 0, 0); ?>%</strong>
        </div>
    </article>
</section>

<?php if (count($datos_temas) > 0) { ?>
    <!-- Paneles de desempeño (Gráfico y Listado) -->
    <div class="paneles-desempeno">
        
        <!-- Panel Izquierdo: Gráfica de Barras -->
        <article class="tarjeta">
            <h2>Gráfica de barras de desempeño</h2>
            <p class="subtitulo" style="margin-bottom: 24px;">Comparativa visual del porcentaje de acierto por cada tema.</p>
            
            <div class="chart-box">
                <?php foreach ($datos_temas as $tema_row) { 
                    $porc = $tema_row['porcentaje_acierto'];
                    // Asignar clase de color
                    $bar_color = "bar-roja";
                    if ($porc >= 80) {
                        $bar_color = "bar-verde";
                    } elseif ($porc >= 50) {
                        $bar_color = "bar-amarilla";
                    }
                ?>
                    <div class="chart-row">
                        <div class="chart-label-group">
                            <span><?php echo htmlspecialchars($tema_row['tema']); ?></span>
                            <span><?php echo number_format($porc, 1); ?>%</span>
                        </div>
                        <div class="chart-bar-bg">
                            <div class="chart-bar-fill <?php echo $bar_color; ?>" data-width="<?php echo $porc; ?>%"></div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </article>

        <!-- Panel Derecho: Tabla detallada -->
        <article class="tarjeta">
            <h2>Listado descriptivo</h2>
            <p class="subtitulo" style="margin-bottom: 16px;">Detalle cuantitativo de aciertos e incorrectas.</p>
            
            <table>
                <thead>
                    <tr>
                        <th>Tema</th>
                        <th style="text-align: center;">Resp.</th>
                        <th style="text-align: center;">Correctas</th>
                        <th style="text-align: center;">Porcentaje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datos_temas as $tema_row) { 
                        $porc = $tema_row['porcentaje_acierto'];
                        $clase_badge = 'badge-rojo';
                        $nivel = 'Bajo';
                        if ($porc >= 80) {
                            $clase_badge = 'badge-verde';
                            $nivel = 'Alto';
                        } elseif ($porc >= 50) {
                            $clase_badge = 'badge-amarillo';
                            $nivel = 'Medio';
                        }
                    ?>
                        <tr>
                            <td style="font-weight: 700; font-size: 13.5px;"><?php echo htmlspecialchars($tema_row['tema']); ?></td>
                            <td style="text-align: center; color: var(--muted);"><?php echo $tema_row['total_respuestas']; ?></td>
                            <td style="text-align: center; color: var(--verde); font-weight: bold;"><?php echo $tema_row['respuestas_correctas']; ?></td>
                            <td>
                                <span class="badge <?php echo $clase_badge; ?>" style="font-size: 11px;">
                                    <?php echo number_format($porc, 0); ?>% - <?php echo $nivel; ?>
                                </span>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </article>
    </div>

    <!-- Sección de Clasificación de Temas (Dominados vs Repaso) -->
    <article class="tarjeta">
        <h2>Clasificación y diagnóstico por temas</h2>
        <p class="subtitulo" style="margin-bottom: 24px;">Identificación de los temas dominados por el grupo y aquellos que requieren reforzamiento académico.</p>
        
        <div class="clasificacion-grid">
            
            <!-- Columna Temas Dominados -->
            <div class="bloque-categoria bloque-dominado">
                <div class="bloque-header header-dominado">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    Temas Dominados (≥ 80% Aciertos)
                </div>
                
                <?php if (count($temas_dominados) > 0) { ?>
                    <ul class="lista-categorias">
                        <?php foreach ($temas_dominados as $tema_row) { ?>
                            <li class="li-dominado">
                                <span><?php echo htmlspecialchars($tema_row['tema']); ?></span>
                                <span style="font-weight: bold;"><?php echo number_format($tema_row['porcentaje_acierto'], 0); ?>%</span>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } else { ?>
                    <p style="color: #166534; font-size: 13px; margin: 0; font-style: italic;">Ningún tema ha alcanzado el nivel de dominio todavía en este examen.</p>
                <?php } ?>
            </div>

            <!-- Columna Temas por Repasar -->
            <div class="bloque-categoria bloque-repaso">
                <div class="bloque-header header-repaso">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    Temas que Requieren Repaso (< 80% Aciertos)
                </div>
                
                <?php if (count($temas_repaso) > 0) { ?>
                    <ul class="lista-categorias">
                        <?php foreach ($temas_repaso as $tema_row) { 
                            $porc = $tema_row['porcentaje_acierto'];
                            $pri_clase = ($porc < 50) ? 'pri-alta' : 'pri-media';
                            $pri_texto = ($porc < 50) ? 'Prioridad Alta' : 'Prioridad Media';
                        ?>
                            <li class="li-repaso">
                                <div>
                                    <span style="display: block;"><?php echo htmlspecialchars($tema_row['tema']); ?></span>
                                    <span class="badge-repaso-prioridad <?php echo $pri_clase; ?>"><?php echo $pri_texto; ?></span>
                                </div>
                                <span style="font-weight: bold;"><?php echo number_format($porc, 0); ?>%</span>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } else { ?>
                    <p style="color: #991b1b; font-size: 13px; margin: 0; font-style: italic;">¡Excelente! Todos los temas evaluados en este examen han sido dominados.</p>
                <?php } ?>
            </div>

        </div>
    </article>

<?php } else { ?>
    <section class="tarjeta" style="text-align: center; padding: 48px 24px;">
        <div style="width: 64px; height: 64px; border-radius: 50%; background: #f1f5f9; color: var(--muted); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        </div>
        <h2>No hay respuestas registradas</h2>
        <p class="subtitulo" style="max-width: 480px; margin: 0 auto 24px;">Aún no se han registrado intentos o respuestas de alumnos para este examen específico. Comparte el código de examen con tus alumnos para comenzar a recibir información de desempeño.</p>
        <a class="boton" href="examenes_generados.php">Ver exámenes generados</a>
    </section>
<?php } ?>

<script>
    // Listener para cambiar de examen en la URL
    document.getElementById('selector-examen').addEventListener('change', function() {
        const id = this.value;
        window.location.href = 'estadisticas.php' + (id !== 'todos' ? '?id_examen=' + id : '');
    });

    // Animación de llenado progresivo para la gráfica de barras
    window.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => {
            document.querySelectorAll('.chart-bar-fill').forEach(bar => {
                const width = bar.getAttribute('data-width');
                bar.style.width = width;
            });
        }, 150);
    });
</script>

<?php include 'includes/footer.php'; ?>
