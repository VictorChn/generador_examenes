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
    <a class="boton boton-secundario" href="dashboard.php">Historial de examenes</a>
</div>

<?php echo $mensaje; ?>

<form class="form-limpio" method="POST" action="generar_examen.php">
    <!-- Hidden input to satisfy the backend logic without modifying it -->
    <input type="hidden" name="titulo" id="titulo_examen" value="Examen Generado Automáticamente">

    <section class="tarjeta" style="display: flex; align-items: center; justify-content: space-between; padding: 24px; margin-bottom: 24px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--azul-600); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">1</div>
            <div>
                <strong style="display: block; color: var(--texto); font-size: 14px;">Configuración</strong>
                <span style="color: var(--muted); font-size: 13px;">Selecciona cantidad y temas</span>
            </div>
        </div>
        <div style="flex: 1; height: 1px; background: var(--linea); margin: 0 20px;"></div>
        <div style="display: flex; align-items: center; gap: 12px; opacity: 0.5;">
            <div style="width: 32px; height: 32px; border-radius: 50%; background: #f0f2f5; color: var(--muted); display: flex; align-items: center; justify-content: center; font-weight: bold;">2</div>
            <div>
                <strong style="display: block; color: var(--texto); font-size: 14px;">Vista previa</strong>
                <span style="color: var(--muted); font-size: 13px;">Revisa las preguntas</span>
            </div>
        </div>
        <div style="flex: 1; height: 1px; background: var(--linea); margin: 0 20px;"></div>
        <div style="display: flex; align-items: center; gap: 12px; opacity: 0.5;">
            <div style="width: 32px; height: 32px; border-radius: 50%; background: #f0f2f5; color: var(--muted); display: flex; align-items: center; justify-content: center; font-weight: bold;">3</div>
            <div>
                <strong style="display: block; color: var(--texto); font-size: 14px;">Generar</strong>
                <span style="color: var(--muted); font-size: 13px;">Obtén el código del examen</span>
            </div>
        </div>
    </section>

    <div class="panel-grid">
        <section class="tarjeta form-limpio" style="border:none; box-shadow:none; padding: 0;">
            <h2 style="font-size: 16px; margin-bottom: 4px;">1. Cantidad de preguntas</h2>
            <p class="subtitulo" style="font-size: 13px; margin-bottom: 16px;">Selecciona el número total de preguntas para el examen.</p>
            
            <div style="display: flex; align-items: center; border: 1px solid var(--linea); border-radius: 8px; width: 100%; margin-bottom: 8px;">
                <button type="button" style="padding: 12px 18px; background: transparent; border: none; font-size: 18px; color: var(--azul-600); cursor: pointer;" onclick="document.getElementById('total_preguntas').stepDown(); actualizarResumen();">&minus;</button>
                <input type="number" name="total_preguntas" id="total_preguntas" min="5" max="100" value="20" style="flex: 1; border: none; text-align: center; font-weight: bold; font-size: 15px; outline: none; padding: 12px 0;" required onchange="actualizarResumen();">
                <button type="button" style="padding: 12px 18px; background: transparent; border: none; font-size: 18px; color: var(--azul-600); cursor: pointer;" onclick="document.getElementById('total_preguntas').stepUp(); actualizarResumen();">&plus;</button>
            </div>
            <small style="color: var(--muted); font-size: 12px; display: block; margin-bottom: 32px;">Mínimo 5 preguntas, máximo 100 preguntas.</small>

            <h2 style="font-size: 16px; margin-bottom: 4px;">2. Seleccionar temas</h2>
            <p class="subtitulo" style="font-size: 13px; margin-bottom: 16px;">Elige el tema que deseas incluir en el examen.</p>
            
            <div style="border: 1px solid var(--linea); border-radius: 8px; overflow: hidden; max-height: 280px; overflow-y: auto;">
                <?php while ($tema = mysqli_fetch_assoc($consulta_temas)) { ?>
                    <label style="display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; border-bottom: 1px solid var(--linea); cursor: pointer; margin: 0; background: #fff;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <input type="radio" name="id_tema" value="<?php echo $tema['id_tema']; ?>" required style="width: 18px; height: 18px; accent-color: var(--azul-600);" onchange="document.getElementById('titulo_examen').value = 'Examen de ' + this.nextElementSibling.innerText; document.getElementById('resumen_temas').innerText = '1';">
                            <span style="font-weight: 600; font-size: 14px; color: var(--texto);"><?php echo htmlspecialchars($tema['nombre']); ?></span>
                        </div>
                        <span style="background: var(--azul-100); color: var(--azul-600); padding: 4px 10px; border-radius: 99px; font-size: 12px; font-weight: 600;">Seleccionar</span>
                    </label>
                <?php } ?>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 12px 16px; background: #f8fafc; border: 1px solid var(--linea); border-top: none; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; font-size: 12px; color: var(--muted);">
                <span>Selección única requerida</span>
                <span style="color: var(--verde); font-weight: 600;">Temas disponibles desde el banco</span>
            </div>
        </section>

        <aside class="tarjeta" style="background: #fff; padding: 24px; display: flex; flex-direction: column;">
            <h2 style="font-size: 16px; color: var(--azul-600); margin-bottom: 4px;">Resumen de la generación</h2>
            <p class="subtitulo" style="font-size: 13px; margin-bottom: 24px;">Revisa la configuración antes de continuar.</p>
            
            <div style="border: 1px solid var(--linea); border-radius: 8px; padding: 16px 20px; display: flex; flex-direction: column; gap: 24px; margin-bottom: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: #eef2ff; color: #4f46e5; display: flex; justify-content: center; align-items: center;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        </div>
                        <span style="font-size: 14px; font-weight: 600;">Cantidad de preguntas</span>
                    </div>
                    <strong id="resumen_cantidad" style="font-size: 14px;">20</strong>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: #ecfdf5; color: #10b981; display: flex; justify-content: center; align-items: center;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                        </div>
                        <span style="font-size: 14px; font-weight: 600;">Temas seleccionados</span>
                    </div>
                    <strong id="resumen_temas" style="font-size: 14px;">0</strong>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: #fef3c7; color: #d97706; display: flex; justify-content: center; align-items: center;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        </div>
                        <span style="font-size: 14px; font-weight: 600;">Preguntas disponibles</span>
                    </div>
                    <strong style="font-size: 14px;">Varias</strong>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: #f3e8ff; color: #a855f7; display: flex; justify-content: center; align-items: center;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                        </div>
                        <span style="font-size: 14px; font-weight: 600;">Método de selección</span>
                    </div>
                    <strong style="font-size: 14px;">Aleatoria</strong>
                </div>
            </div>

            <div style="background: #f0f7ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 16px; display: flex; gap: 12px; margin-bottom: auto;">
                <div style="color: var(--azul-600); flex-shrink: 0;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                </div>
                <div>
                    <strong style="display: block; font-size: 13px; color: var(--azul-800); margin-bottom: 4px;">¿Cómo funciona?</strong>
                    <span style="font-size: 12.5px; color: #334155; line-height: 1.5; display: block;">El sistema seleccionará aleatoriamente las preguntas del banco que correspondan al tema elegido, garantizando variedad y equilibrio en el examen.</span>
                </div>
            </div>
        </aside>
    </div>

    <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
        <button type="submit" class="boton" style="padding: 0 24px; min-height: 48px; border-radius: 8px;">Continuar a vista previa &rarr;</button>
    </div>
</form>

<script>
function actualizarResumen() {
    const val = document.getElementById('total_preguntas').value;
    document.getElementById('resumen_cantidad').innerText = val;
}
</script>

<?php include 'includes/footer.php'; ?>
