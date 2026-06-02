<?php
include 'config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['nombre_estudiante']) || empty($_POST['codigo_examen'])) {
    header("Location: alumno_login.php");
    exit;
}

$nombre_estudiante = $_POST['nombre_estudiante'];
$codigo_examen_raw = strtoupper(trim($_POST['codigo_examen']));

// Extraer el ID numérico del código (ej. EX-2024-015 -> 15)
if (preg_match('/(\d+)$/', $codigo_examen_raw, $matches)) {
    $id_examen = intval($matches[1]);
} else {
    // Si no tiene formato esperado, intentamos usarlo como ID
    $id_examen = intval($codigo_examen_raw);
}

// Buscar el examen
$stmt_examen = mysqli_prepare($conexion, "SELECT * FROM examenes WHERE id_examen = ?");
mysqli_stmt_bind_param($stmt_examen, "i", $id_examen);
mysqli_stmt_execute($stmt_examen);
$resultado_examen = mysqli_stmt_get_result($stmt_examen);
$examen = mysqli_fetch_assoc($resultado_examen);

if (!$examen) {
    // Código inválido
    echo "<script>alert('Código de examen no válido o no encontrado.'); window.location.href='alumno_login.php';</script>";
    exit;
}

// Validar que el código ingresado coincida exactamente con el código esperado
$codigo_esperado = 'EX-' . date('Y', strtotime($examen['fecha_creacion'] ?? date('Y-m-d'))) . '-' . str_pad($examen['id_examen'], 3, '0', STR_PAD_LEFT);

if ($codigo_examen_raw !== $codigo_esperado) {
    echo "<script>alert('El código ingresado es incorrecto o no existe en la base de datos.'); window.location.href='alumno_login.php';</script>";
    exit;
}

// Buscar las preguntas
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

$preguntas = [];
while ($row = mysqli_fetch_assoc($resultado_preguntas)) {
    $preguntas[] = $row;
}

$total_preguntas = count($preguntas);
if ($total_preguntas === 0) {
    echo "<script>alert('El examen no tiene preguntas.'); window.location.href='alumno_login.php';</script>";
    exit;
}

$codigo_display = 'EX-' . date('Y', strtotime($examen['fecha_creacion'] ?? date('Y-m-d'))) . '-' . str_pad($examen['id_examen'], 3, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examen: <?php echo htmlspecialchars($examen['titulo']); ?></title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        body {
            background-color: #f8fafc;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #1e293b;
        }

        .exam-header {
            background: #fff;
            border-bottom: 1px solid var(--linea);
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .exam-header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .exam-header-left img {
            height: 85px;
            width: auto;
        }

        .exam-header-center {
            text-align: center;
        }

        .exam-header-center h1 {
            margin: 0;
            font-size: 18px;
            color: var(--azul-900);
        }

        .exam-header-center span {
            font-size: 13px;
            color: var(--muted);
        }

        .exam-header-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .student-avatar {
            width: 36px;
            height: 36px;
            background: #f1f5f9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--muted);
        }

        .student-info {
            text-align: right;
        }
        
        .student-info strong {
            display: block;
            font-size: 14px;
            color: var(--texto);
        }
        
        .student-info span {
            font-size: 12px;
            color: var(--muted);
        }

        .exam-layout {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 24px;
            max-width: 1200px;
            margin: 32px auto;
            padding: 0 24px;
        }

        .question-card {
            background: #fff;
            border: 1px solid var(--linea);
            border-radius: 12px;
            padding: 32px;
            display: none;
        }

        .question-card.active {
            display: block;
        }

        .progress-container {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 32px;
        }

        .progress-text {
            font-size: 14px;
            font-weight: 600;
            color: var(--texto);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .progress-bar-bg {
            flex: 1;
            height: 8px;
            background: #f1f5f9;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: var(--azul-600);
            width: 0%;
            transition: width 0.3s;
        }

        .progress-percent {
            font-size: 13px;
            color: var(--muted);
        }

        .question-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--texto);
            margin: 0 0 24px 0;
            line-height: 1.5;
        }

        .option-label {
            display: flex;
            align-items: center;
            padding: 16px 20px;
            border: 1px solid var(--linea);
            border-radius: 8px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 15px;
            color: var(--texto);
        }

        .option-label:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .option-label.selected {
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .option-label input[type="radio"] {
            margin-right: 16px;
            width: 18px;
            height: 18px;
            accent-color: var(--azul-600);
        }

        .question-hint {
            background: #eff6ff;
            color: #1e40af;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            display: flex;
            gap: 8px;
            margin-top: 24px;
            align-items: center;
        }

        .nav-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--linea);
        }

        .btn-outline {
            background: #fff;
            border: 1px solid var(--linea);
            color: var(--texto);
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-outline:hover {
            background: #f8fafc;
        }
        
        .btn-outline:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-primary {
            background: var(--azul-600);
            border: none;
            color: #fff;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: var(--azul-700);
        }

        .sidebar-right {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .grid-card {
            background: #fff;
            border: 1px solid var(--linea);
            border-radius: 12px;
            padding: 24px;
        }

        .grid-title {
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 16px 0;
            color: var(--texto);
        }

        .nav-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 8px;
            margin-bottom: 20px;
        }

        .nav-dot {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--linea);
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            color: var(--muted);
            cursor: pointer;
            transition: all 0.2s;
            background: #fff;
        }

        .nav-dot.answered {
            background: #f0fdf4;
            border-color: #bbf7d0;
            color: #16a34a;
        }

        .nav-dot.current {
            border-color: var(--azul-600);
            color: var(--azul-600);
            background: #eff6ff;
        }

        .nav-legend {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: var(--muted);
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .legend-box {
            width: 12px;
            height: 12px;
            border-radius: 3px;
            border: 1px solid;
        }
        
        .legend-box.l-answered { background: #f0fdf4; border-color: #bbf7d0; }
        .legend-box.l-current { background: #eff6ff; border-color: var(--azul-600); }
        .legend-box.l-unanswered { background: #fff; border-color: var(--linea); }

        .info-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .info-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            color: var(--texto);
        }

        .info-list svg {
            color: var(--muted);
        }

        .btn-finish {
            width: 100%;
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
            padding: 14px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            margin-top: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }
        
        .btn-finish:hover {
            background: #fee2e2;
        }

        .footer-logo {
            text-align: center;
            padding: 40px 20px;
            color: var(--muted);
            font-size: 12px;
        }
    </style>
</head>
<body>

<form id="examForm" method="POST" action="calificar.php">
    <input type="hidden" name="id_examen" value="<?php echo $examen['id_examen']; ?>">
    <input type="hidden" name="nombre_estudiante" value="<?php echo htmlspecialchars($nombre_estudiante); ?>">

    <header class="exam-header">
        <div class="exam-header-left">
            <img src="imagenes/Logo.png" alt="Logo">
        </div>
        <div class="exam-header-center">
            <h1>Examen: <?php echo htmlspecialchars($examen['titulo']); ?></h1>
            <span>Código: <?php echo $codigo_display; ?></span>
        </div>
        <div class="exam-header-right">
            <div class="student-avatar">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            </div>
            <div class="student-info">
                <strong><?php echo htmlspecialchars($nombre_estudiante); ?></strong>
                <span>Estudiante</span>
            </div>
        </div>
    </header>

    <div class="exam-layout">
        <main>
            <?php foreach ($preguntas as $index => $pregunta) { 
                $qNum = $index + 1;
            ?>
                <div class="question-card" id="card_<?php echo $qNum; ?>">
                    <div class="progress-container">
                        <div class="progress-text">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--azul-600)" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            Pregunta <?php echo $qNum; ?> de <?php echo $total_preguntas; ?>
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: <?php echo ($qNum / $total_preguntas) * 100; ?>%;"></div>
                        </div>
                        <div class="progress-percent"><?php echo round(($qNum / $total_preguntas) * 100); ?>% completado</div>
                    </div>

                    <h2 class="question-title"><?php echo $qNum; ?>. <?php echo htmlspecialchars($pregunta['enunciado']); ?></h2>

                    <?php if ($pregunta['tipo'] == 'opcion_multiple') { ?>
                        <label class="option-label" onclick="selectOption(this, <?php echo $qNum; ?>)">
                            <input type="radio" name="respuestas[<?php echo $pregunta['id_pregunta']; ?>]" value="A" required>
                            A. <?php echo htmlspecialchars($pregunta['opcion_a']); ?>
                        </label>
                        <label class="option-label" onclick="selectOption(this, <?php echo $qNum; ?>)">
                            <input type="radio" name="respuestas[<?php echo $pregunta['id_pregunta']; ?>]" value="B">
                            B. <?php echo htmlspecialchars($pregunta['opcion_b']); ?>
                        </label>
                        <label class="option-label" onclick="selectOption(this, <?php echo $qNum; ?>)">
                            <input type="radio" name="respuestas[<?php echo $pregunta['id_pregunta']; ?>]" value="C">
                            C. <?php echo htmlspecialchars($pregunta['opcion_c']); ?>
                        </label>
                        <label class="option-label" onclick="selectOption(this, <?php echo $qNum; ?>)">
                            <input type="radio" name="respuestas[<?php echo $pregunta['id_pregunta']; ?>]" value="D">
                            D. <?php echo htmlspecialchars($pregunta['opcion_d']); ?>
                        </label>
                    <?php } else { ?>
                        <label class="option-label" onclick="selectOption(this, <?php echo $qNum; ?>)">
                            <input type="radio" name="respuestas[<?php echo $pregunta['id_pregunta']; ?>]" value="Verdadero" required>
                            Verdadero
                        </label>
                        <label class="option-label" onclick="selectOption(this, <?php echo $qNum; ?>)">
                            <input type="radio" name="respuestas[<?php echo $pregunta['id_pregunta']; ?>]" value="Falso">
                            Falso
                        </label>
                    <?php } ?>

                    <div class="question-hint">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        Selecciona la opción correcta.
                    </div>

                    <div class="nav-buttons">
                        <button type="button" class="btn-outline" onclick="prevQuestion()" id="btnPrev_<?php echo $qNum; ?>">&larr; Anterior</button>
                        <?php if ($qNum < $total_preguntas) { ?>
                            <button type="button" class="btn-primary" onclick="nextQuestion()">Siguiente &rarr;</button>
                        <?php } else { ?>
                            <div style="width: 100px;"></div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </main>

        <aside class="sidebar-right">
            <div class="grid-card">
                <h3 class="grid-title">Navegación</h3>
                <div class="nav-grid">
                    <?php for ($i = 1; $i <= $total_preguntas; $i++) { ?>
                        <div class="nav-dot" id="dot_<?php echo $i; ?>" onclick="goToQuestion(<?php echo $i; ?>)"><?php echo $i; ?></div>
                    <?php } ?>
                </div>
                <div class="nav-legend">
                    <div class="legend-item"><div class="legend-box l-answered"></div> Respondida</div>
                    <div class="legend-item"><div class="legend-box l-current"></div> Actual</div>
                    <div class="legend-item"><div class="legend-box l-unanswered"></div> Sin responder</div>
                </div>
            </div>

            <div class="grid-card">
                <h3 class="grid-title">Información del examen</h3>
                <ul class="info-list">
                    <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path></svg> Total de preguntas: <?php echo $total_preguntas; ?></li>
                    <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg> Tipo: Variado</li>
                    <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> Duración: Sin límite</li>
                    <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> Código: <?php echo $codigo_display; ?></li>
                </ul>
                <button type="button" class="btn-finish" onclick="confirmFinish()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    Finalizar examen
                </button>
            </div>
        </aside>
    </div>
    
    <div class="footer-logo">
        <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg> Sistema de Exámenes Institucional
        </div>
        &copy; <?php echo date('Y'); ?> Instituto Educativo. Todos los derechos reservados.
    </div>
</form>

<script>
    const totalQuestions = <?php echo $total_preguntas; ?>;
    let currentQuestion = 1;

    function init() {
        showQuestion(1);
        document.getElementById('btnPrev_1').disabled = true;
    }

    function showQuestion(num) {
        document.querySelectorAll('.question-card').forEach(card => card.classList.remove('active'));
        document.querySelectorAll('.nav-dot').forEach(dot => dot.classList.remove('current'));
        
        document.getElementById('card_' + num).classList.add('active');
        document.getElementById('dot_' + num).classList.add('current');
        currentQuestion = num;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function nextQuestion() {
        if (currentQuestion < totalQuestions) {
            showQuestion(currentQuestion + 1);
        }
    }

    function prevQuestion() {
        if (currentQuestion > 1) {
            showQuestion(currentQuestion - 1);
        }
    }

    function goToQuestion(num) {
        showQuestion(num);
    }

    function selectOption(labelElement, qNum) {
        // Find the card
        const card = document.getElementById('card_' + qNum);
        // Remove selected class from all labels in this card
        card.querySelectorAll('.option-label').forEach(lbl => lbl.classList.remove('selected'));
        // Add to the clicked one
        labelElement.classList.add('selected');
        // Mark dot as answered
        document.getElementById('dot_' + qNum).classList.add('answered');
    }

    function confirmFinish() {
        // Check if all answered
        const answeredCount = document.querySelectorAll('.nav-dot.answered').length;
        if (answeredCount < totalQuestions) {
            if (!confirm(`Aún tienes ${totalQuestions - answeredCount} preguntas sin responder. ¿Estás seguro que deseas finalizar el examen?`)) {
                return;
            }
        } else {
            if (!confirm("Has respondido todas las preguntas. ¿Deseas finalizar y enviar tu examen?")) {
                return;
            }
        }
        document.getElementById('examForm').submit();
    }

    // Initialize
    init();
</script>

</body>
</html>
