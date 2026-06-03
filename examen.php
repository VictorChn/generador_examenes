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
    SELECT preguntas.*, temas.nombre AS tema_nombre
    FROM examen_preguntas
    INNER JOIN preguntas ON examen_preguntas.id_pregunta = preguntas.id_pregunta
    LEFT JOIN temas ON preguntas.id_tema = temas.id_tema
    WHERE examen_preguntas.id_examen = ?
";

$stmt_preguntas = mysqli_prepare($conexion, $sql_preguntas);
mysqli_stmt_bind_param($stmt_preguntas, "i", $id_examen);
mysqli_stmt_execute($stmt_preguntas);
$resultado_preguntas = mysqli_stmt_get_result($stmt_preguntas);

// Store questions in array for rendering and counting themes
$preguntas = [];
$conteo_temas = [];
while ($row = mysqli_fetch_assoc($resultado_preguntas)) {
    $preguntas[] = $row;
    $tema = $row['tema_nombre'] ?? 'Sin tema';
    if (!isset($conteo_temas[$tema])) {
        $conteo_temas[$tema] = 0;
    }
    $conteo_temas[$tema]++;
}

$codigo_examen = 'EX-' . date('Y') . '-' . str_pad($examen['id_examen'], 3, '0', STR_PAD_LEFT);
$fecha_formateada = date("d 'de' M. 'de' Y h:i a", strtotime($examen['fecha_creacion'] ?? date('Y-m-d H:i:s')));

include 'includes/header.php';
?>

<style>
.step-hidden { display: none !important; }
.stepper-container { display: flex; align-items: center; justify-content: space-between; border: 1px solid var(--linea); border-radius: 8px; padding: 24px; margin-bottom: 24px; background: #fff; }
.stepper-item { display: flex; align-items: center; gap: 12px; }
.stepper-circle { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
.stepper-circle.active { background: var(--azul-600); color: white; }
.stepper-circle.completed { background: #ecfdf5; color: #10b981; }
.stepper-circle.inactive { background: #f0f2f5; color: var(--muted); }
.stepper-line { flex: 1; height: 2px; background: var(--linea); margin: 0 20px; }
.stepper-line.completed { background: #10b981; }
</style>

<!-- ================= STEP 2: VISTA PREVIA ================= -->
<div id="step2_view">
    <div style="margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <a href="generar_examen.php" style="color: var(--muted); text-decoration: none; font-size: 14px; margin-bottom: 8px; display: inline-block;">&larr; Generar examen</a>
            <h1 style="margin: 0; font-size: 24px; color: var(--texto);">Vista previa del examen</h1>
            <p style="margin: 4px 0 0 0; color: var(--muted); font-size: 14px;">Revisa las preguntas seleccionadas antes de generar el código del examen.</p>
        </div>
        <div style="display: flex; gap: 12px;">
            <a class="boton boton-secundario" href="generar_examen.php"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> Editar configuración</a>
            <button class="boton" onclick="showStep3()">Generar examen</button>
        </div>
    </div>

    <section class="stepper-container">
        <div class="stepper-item">
            <div class="stepper-circle completed"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></div>
            <div>
                <strong style="display: block; color: var(--texto); font-size: 14px;">1. Configuración</strong>
                <span style="color: var(--muted); font-size: 13px;">Cantidad y temas seleccionados</span>
            </div>
        </div>
        <div class="stepper-line completed"></div>
        <div class="stepper-item">
            <div class="stepper-circle active">2</div>
            <div>
                <strong style="display: block; color: var(--texto); font-size: 14px;">Vista previa</strong>
                <span style="color: var(--muted); font-size: 13px;">Revisa las preguntas</span>
            </div>
        </div>
        <div class="stepper-line"></div>
        <div class="stepper-item" style="opacity: 0.5;">
            <div class="stepper-circle inactive">3</div>
            <div>
                <strong style="display: block; color: var(--texto); font-size: 14px;">Generar</strong>
                <span style="color: var(--muted); font-size: 13px;">Obtén el código del examen</span>
            </div>
        </div>
    </section>

    <div class="panel-grid">
        <section style="background: #fff; border: 1px solid var(--linea); border-radius: 8px; padding: 24px;">
            <h2 style="font-size: 18px; margin-bottom: 16px;">Preguntas del examen (<?php echo count($preguntas); ?>)</h2>
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px 16px; display: flex; gap: 12px; margin-bottom: 24px; color: #1e3a8a; font-size: 13px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <span>Estas son las preguntas seleccionadas aleatoriamente del banco según tu configuración.</span>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($preguntas as $index => $pregunta) { ?>
                    <div style="border: 1px solid var(--linea); border-radius: 8px; display: flex; overflow: hidden;">
                        <div style="background: #f8fafc; padding: 20px; font-weight: bold; color: var(--muted); border-right: 1px solid var(--linea); width: 60px; text-align: center; display: flex; align-items: center; justify-content: center;">
                            <?php echo $index + 1; ?>
                        </div>
                        <div style="padding: 20px; flex: 1;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 12px;">
                                <span style="background: #f1f5f9; color: #475569; padding: 4px 8px; border-radius: 4px; font-weight: 600;"><?php echo htmlspecialchars($pregunta['tema_nombre'] ?? 'Sin tema'); ?></span>
                                <span style="color: var(--azul-600); font-weight: 600;"><?php echo $pregunta['tipo'] == 'opcion_multiple' ? 'Opción múltiple' : 'Verdadero/Falso'; ?></span>
                            </div>
                            <strong style="display: block; font-size: 15px; margin-bottom: 16px; color: var(--texto);"><?php echo htmlspecialchars($pregunta['enunciado']); ?></strong>
                            
                            <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px;">
                                <?php if ($pregunta['tipo'] == 'opcion_multiple') { ?>
                                    <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--muted);"><input type="radio" disabled <?php echo $pregunta['respuesta_correcta'] == 'A' ? 'checked' : ''; ?>> a) <?php echo htmlspecialchars($pregunta['opcion_a']); ?></label>
                                    <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--muted);"><input type="radio" disabled <?php echo $pregunta['respuesta_correcta'] == 'B' ? 'checked' : ''; ?>> b) <?php echo htmlspecialchars($pregunta['opcion_b']); ?></label>
                                    <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--muted);"><input type="radio" disabled <?php echo $pregunta['respuesta_correcta'] == 'C' ? 'checked' : ''; ?>> c) <?php echo htmlspecialchars($pregunta['opcion_c']); ?></label>
                                    <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--muted);"><input type="radio" disabled <?php echo $pregunta['respuesta_correcta'] == 'D' ? 'checked' : ''; ?>> d) <?php echo htmlspecialchars($pregunta['opcion_d']); ?></label>
                                <?php } else { ?>
                                    <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--muted);"><input type="radio" disabled <?php echo $pregunta['respuesta_correcta'] == 'Verdadero' ? 'checked' : ''; ?>> Verdadero</label>
                                    <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--muted);"><input type="radio" disabled <?php echo $pregunta['respuesta_correcta'] == 'Falso' ? 'checked' : ''; ?>> Falso</label>
                                <?php } ?>
                            </div>
                            <div style="background: #ecfdf5; color: #065f46; padding: 10px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                Respuesta correcta: <?php echo $pregunta['respuesta_correcta']; ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--linea);">
                <a href="generar_examen.php" class="boton boton-secundario">&larr; Volver a configuración</a>
                <button class="boton" onclick="showStep3()">Continuar a generar código &rarr;</button>
            </div>
        </section>

        <aside style="display: flex; flex-direction: column; gap: 24px;">
            <div class="tarjeta" style="background: #fff; padding: 24px;">
                <h2 style="font-size: 16px; color: var(--texto); margin-bottom: 24px;">Resumen del examen</h2>
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 28px; height: 28px; border-radius: 50%; background: #eef2ff; color: #4f46e5; display: flex; justify-content: center; align-items: center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></div>
                            <span style="font-size: 13px; font-weight: 600; color: var(--muted);">Cantidad de preguntas</span>
                        </div>
                        <strong style="font-size: 14px;"><?php echo count($preguntas); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 28px; height: 28px; border-radius: 50%; background: #ecfdf5; color: #10b981; display: flex; justify-content: center; align-items: center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path></svg></div>
                            <span style="font-size: 13px; font-weight: 600; color: var(--muted);">Temas incluidos</span>
                        </div>
                        <strong style="font-size: 14px;"><?php echo count($conteo_temas); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 28px; height: 28px; border-radius: 50%; background: #fef3c7; color: #d97706; display: flex; justify-content: center; align-items: center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path></svg></div>
                            <span style="font-size: 13px; font-weight: 600; color: var(--muted);">Preguntas disponibles</span>
                        </div>
                        <strong style="font-size: 14px;">Varias</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 28px; height: 28px; border-radius: 50%; background: #f3e8ff; color: #a855f7; display: flex; justify-content: center; align-items: center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon></svg></div>
                            <span style="font-size: 13px; font-weight: 600; color: var(--muted);">Método de selección</span>
                        </div>
                        <strong style="font-size: 14px;">Aleatoria</strong>
                    </div>
                </div>
            </div>

            <div class="tarjeta" style="background: #fff; padding: 24px;">
                <h2 style="font-size: 16px; color: var(--texto); margin-bottom: 20px;">Temas incluidos</h2>
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <?php 
                    $colores = ['#2563eb', '#10b981', '#d97706', '#8b5cf6'];
                    $i = 0;
                    foreach ($conteo_temas as $tema_nombre => $cantidad) { 
                        $color = $colores[$i % count($colores)];
                        $i++;
                    ?>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 8px; height: 8px; border-radius: 50%; background: <?php echo $color; ?>;"></div>
                                <strong style="font-size: 14px; color: var(--texto);"><?php echo htmlspecialchars($tema_nombre); ?></strong>
                            </div>
                            <span style="font-size: 13px; color: var(--muted);"><?php echo $cantidad; ?> preguntas</span>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div style="background: #f0f7ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 16px; display: flex; gap: 12px;">
                <div style="color: var(--azul-600); flex-shrink: 0;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg></div>
                <div>
                    <strong style="display: block; font-size: 13px; color: var(--azul-800); margin-bottom: 4px;">Importante</strong>
                    <span style="font-size: 12.5px; color: #334155; line-height: 1.5; display: block;">Al generar el examen, se asignará un código único que tus alumnos usarán para acceder a él.</span>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ================= STEP 3: EXAMEN GENERADO ================= -->
<div id="step3_view" class="step-hidden">
    <div style="margin-bottom: 24px;">
        <a href="generar_examen.php" style="color: var(--muted); text-decoration: none; font-size: 14px; margin-bottom: 8px; display: inline-block;">&larr; Generar examen</a>
        <h1 style="margin: 0; font-size: 24px; color: var(--texto);">Generar examen</h1>
        <p style="margin: 4px 0 0 0; color: var(--muted); font-size: 14px;">El examen ha sido generado exitosamente.</p>
    </div>

    <section class="stepper-container">
        <div class="stepper-item">
            <div class="stepper-circle completed"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></div>
            <div>
                <strong style="display: block; color: var(--texto); font-size: 14px;">1. Configuración</strong>
                <span style="color: var(--muted); font-size: 13px;">Cantidad y temas seleccionados</span>
            </div>
        </div>
        <div class="stepper-line completed"></div>
        <div class="stepper-item">
            <div class="stepper-circle completed"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></div>
            <div>
                <strong style="display: block; color: var(--texto); font-size: 14px;">Vista previa</strong>
                <span style="color: var(--muted); font-size: 13px;">Revisa las preguntas</span>
            </div>
        </div>
        <div class="stepper-line completed"></div>
        <div class="stepper-item">
            <div class="stepper-circle active">3</div>
            <div>
                <strong style="display: block; color: var(--texto); font-size: 14px;">Generar</strong>
                <span style="color: var(--muted); font-size: 13px;">Obtén el código del examen</span>
            </div>
        </div>
    </section>

    <div class="panel-grid">
        <section style="background: #fff; border: 1px solid var(--linea); border-radius: 8px; padding: 48px 24px; text-align: center; display: flex; flex-direction: column; align-items: center;">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; margin-bottom: 24px;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <h2 style="font-size: 24px; margin-bottom: 8px; color: var(--texto);">¡Examen generado exitosamente!</h2>
            <p style="color: var(--muted); font-size: 15px; margin-bottom: 32px;">Tu examen ha sido creado y está listo para ser aplicado.</p>
            
            <div style="width: 100%; max-width: 400px; border-top: 1px solid var(--linea); padding-top: 32px; margin-bottom: 32px;">
                <span style="display: block; font-size: 14px; font-weight: bold; margin-bottom: 12px; color: var(--texto);">Código del examen</span>
                <div style="display: flex; align-items: center; justify-content: center; gap: 12px; background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 8px; padding: 16px;">
                    <strong style="font-size: 32px; color: var(--azul-600); letter-spacing: 2px;"><?php echo $codigo_examen; ?></strong>
                    <button style="background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px; cursor: pointer; color: var(--texto);" onclick="navigator.clipboard.writeText('<?php echo $codigo_examen; ?>'); alert('Código copiado al portapapeles');" title="Copiar código">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                    </button>
                </div>
            </div>

            <div style="background: #f0f7ff; color: #1e40af; border-radius: 8px; padding: 16px; font-size: 13px; max-width: 400px; display: flex; gap: 12px; text-align: left; margin-bottom: 32px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <span>Comparte este código con tus alumnos para que puedan acceder al examen. El código es único y solo se puede usar para este examen.</span>
            </div>

            <div style="background: #f8fafc; border: 1px solid var(--linea); border-radius: 8px; padding: 20px; text-align: left; width: 100%;">
                <div style="display: flex; align-items: center; gap: 8px; color: var(--azul-600); font-weight: bold; margin-bottom: 12px; font-size: 14px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    Información importante
                </div>
                <ul style="list-style: none; padding: 0; margin: 0; color: #475569; font-size: 13.5px; display: flex; flex-direction: column; gap: 8px;">
                    <li style="display: flex; gap: 8px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" style="flex-shrink: 0; margin-top: 2px;"><polyline points="20 6 9 17 4 12"></polyline></svg> Los alumnos ingresarán su nombre y este código para acceder al examen.</li>
                    <li style="display: flex; gap: 8px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" style="flex-shrink: 0; margin-top: 2px;"><polyline points="20 6 9 17 4 12"></polyline></svg> Una vez respondido, el sistema calificará automáticamente.</li>
                    <li style="display: flex; gap: 8px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" style="flex-shrink: 0; margin-top: 2px;"><polyline points="20 6 9 17 4 12"></polyline></svg> Podrás ver los resultados en la sección de Resultados.</li>
                </ul>
            </div>
        </section>

        <aside style="display: flex; flex-direction: column; gap: 24px;">
            <div class="tarjeta" style="background: #fff; padding: 24px;">
                <h2 style="font-size: 16px; color: var(--texto); margin-bottom: 24px;">Resumen del examen generado</h2>
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 28px; height: 28px; border-radius: 50%; background: #eef2ff; color: #4f46e5; display: flex; justify-content: center; align-items: center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect></svg></div>
                            <span style="font-size: 13px; font-weight: 600; color: var(--muted);">Cantidad de preguntas</span>
                        </div>
                        <strong style="font-size: 14px;"><?php echo count($preguntas); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 28px; height: 28px; border-radius: 50%; background: #ecfdf5; color: #10b981; display: flex; justify-content: center; align-items: center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path></svg></div>
                            <span style="font-size: 13px; font-weight: 600; color: var(--muted);">Temas incluidos</span>
                        </div>
                        <strong style="font-size: 14px;"><?php echo count($conteo_temas); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 28px; height: 28px; border-radius: 50%; background: #fef3c7; color: #d97706; display: flex; justify-content: center; align-items: center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path></svg></div>
                            <span style="font-size: 13px; font-weight: 600; color: var(--muted);">Método de selección</span>
                        </div>
                        <strong style="font-size: 14px;">Aleatoria</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 28px; height: 28px; border-radius: 50%; background: #fdf4ff; color: #c026d3; display: flex; justify-content: center; align-items: center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></div>
                            <span style="font-size: 13px; font-weight: 600; color: var(--muted);">Fecha de generación</span>
                        </div>
                        <strong style="font-size: 13px; text-align: right; line-height: 1.4;"><?php echo $fecha_formateada; ?></strong>
                    </div>
                </div>
            </div>

            <div>
                <h2 style="font-size: 16px; color: var(--texto); margin-bottom: 16px;">¿Qué deseas hacer ahora?</h2>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <button class="boton boton-secundario" style="justify-content: space-between; padding: 16px; background: #fff; text-align: left;" onclick="showStep2()">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <div>
                                <strong style="display: block; font-size: 14px;">Ver vista previa del examen</strong>
                                <span style="font-size: 12px; font-weight: normal; color: var(--muted);">Revisa nuevamente las preguntas del examen</span>
                            </div>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </button>
                    <a href="exportar_pdf.php?id=<?php echo $id_examen; ?>" class="boton boton-secundario" style="justify-content: space-between; padding: 16px; background: #fff; text-align: left;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            <div>
                                <strong style="display: block; font-size: 14px;">Exportar a PDF</strong>
                                <span style="font-size: 12px; font-weight: normal; color: var(--muted);">Descarga el examen en formato PDF</span>
                            </div>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </a>
                    <button class="boton boton-secundario" style="justify-content: space-between; padding: 16px; background: #fff; text-align: left;" onclick="navigator.clipboard.writeText(window.location.href); alert('Enlace copiado');">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                            <div>
                                <strong style="display: block; font-size: 14px;">Copiar enlace para compartir</strong>
                                <span style="font-size: 12px; font-weight: normal; color: var(--muted);">Obtén el enlace directo del examen</span>
                            </div>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </button>
                    <a href="examenes_generados.php" class="boton" style="justify-content: space-between; padding: 16px; text-align: left; min-height: auto;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                            <div>
                                <strong style="display: block; font-size: 14px; color: #fff;">Ir a exámenes generados</strong>
                                <span style="font-size: 12px; font-weight: normal; color: rgba(255,255,255,0.8);">Ver todos los exámenes creados</span>
                            </div>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </a>
                </div>
            </div>
        </aside>
    </div>
</div>

<script>
function showStep3() {
    document.getElementById('step2_view').classList.add('step-hidden');
    document.getElementById('step3_view').classList.remove('step-hidden');
}
function showStep2() {
    document.getElementById('step3_view').classList.add('step-hidden');
    document.getElementById('step2_view').classList.remove('step-hidden');
}
</script>

<?php include 'includes/footer.php'; ?>
