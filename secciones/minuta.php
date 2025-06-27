<?php
// Configuración de errores (solo para desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Manejo de sesión seguro
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexión a la base de datos con ruta absoluta
require_once __DIR__ . '/../includes/db.php';

// Verificar si $obra está definida
if (!isset($obra)) {
    die("<div class='alert alert-danger'>Error: No se ha cargado la información de la obra.</div>");
}

// Verificar sesión de usuario
if (!isset($_SESSION['usuario_id'], $_SESSION['municipio_id'], $_SESSION['anio'])) {
    header("Location: ../index.php");
    exit;
}

// Obtener datos del municipio
$municipio_id = $_SESSION['municipio_id'];
$anio = $_SESSION['anio'];

$stmt = $conn->prepare("SELECT nombre FROM municipios WHERE id = ?");
$stmt->execute([$municipio_id]);
$municipio = $stmt->fetchColumn();
$obra['municipio'] = $municipio;

// Obtener ID de la estimación desde la URL
$estimacion_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Consulta para obtener la estimación seleccionada
$stmtEst = $conn->prepare("SELECT * FROM estimaciones WHERE id = ? AND obra_id = ?");
$stmtEst->execute([$estimacion_id, $obra['id']]);
$estimacion_seleccionada = $stmtEst->fetch(PDO::FETCH_ASSOC);

if (!$estimacion_seleccionada) {
    die("<div class='alert alert-danger'>Error: Estimación no encontrada</div>");
}

// Consulta para obtener datos completos de la obra
$stmtObra = $conn->prepare("SELECT * FROM obras WHERE id = ?");
$stmtObra->execute([$obra['id']]);
$obra = $stmtObra->fetch(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Minuta Informativa de Avance</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <link rel="stylesheet" href="assets/css/minuta.css">
    <link rel="icon" href="assets/img/logo_redondo.png" type="image/x-icon">
</head>
<body>
    <div class="form-container">
          <!-- Mostrar mensajes -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <h2>MINUTA INFORMATIVA DE AVANCE FÍSICO Y FINANCIERO</h2>
        <div class="form-group">
            <label>OBRA:</label>
            <input type="text" value="<?= htmlspecialchars($obra['nombre']) ?>" readonly>
        </div>
        
        <div class="form-group">
            <label>DESCRIPCIÓN:</label>
            <textarea readonly><?= htmlspecialchars($obra['descripcion']) ?></textarea>
        </div>
        
        <div class="form-group">
            <label>ESTIMACIÓN:</label>
            <input type="text" value="<?= htmlspecialchars($estimacion_seleccionada['numero_estimacion']) ?>" readonly>
        </div>

        <form id="minutaForm" action="secciones/guardar_minuta.php" method="POST">
            <input type="hidden" name="obra_id" value="<?= $obra['id'] ?>">
            <input type="hidden" name="estimacion_id" value="<?= $estimacion_id ?>">
            
            <table>
                <tr>
                    <th colspan="2">DATOS DE AVANCE</th>
                </tr>
                <tr>
                    <td><strong>Avance físico general:</strong></td>
                    <td><input type="text" name="avance_fisico" placeholder="Ej: 75%"></td>
                </tr>
                <tr>
                    <td><strong>Avance financiero general:</strong></td>
                    <td><input type="text" name="avance_financiero" placeholder="Ej: 68%"></td>
                </tr>
                <tr>
                    <td><strong>Número de conceptos que forman parte de los alcances del contrato:</strong></td>
                    <td><input type="number" name="conceptos_contrato"></td>
                </tr>
                <tr>
                    <td><strong>Número de conceptos ejecutados a la fecha:</strong></td>
                    <td><input type="number" name="conceptos_ejecutados"></td>
                </tr>
                <tr>
                    <td><strong>Partidas ejecutadas:</strong></td>
                    <td><input type="text" name="partidas_ejecutadas" placeholder="Separadas por coma"></td>
                </tr>
                <tr>
                    <td><strong>Número de conceptos por ejecutar:</strong></td>
                    <td><input type="number" name="conceptos_por_ejecutar"></td>
                </tr>
                <tr>
                    <td><strong>Partidas por ejecutar:</strong></td>
                    <td><input type="text" name="partidas_por_ejecutar" placeholder="Separadas por coma"></td>
                </tr>
                <tr>
                    <td><strong>Fecha de cálculo:</strong></td>
                    <td>
                        <input type="date" name="fecha_calculo" id="fechaCalculo" required>
                        <input type="hidden" name="dias_transcurridos" id="diasTranscurridos">
                    </td>
                </tr>
                <tr>
                    <td><strong>Días transcurridos desde el inicio de los trabajos:</strong></td>
                    <td id="diasResultado">0</td>
                </tr>
                <tr>
                    <td><strong>Número de conceptos extraordinarios solicitados y autorizados:</strong></td>
                    <td><input type="number" name="conceptos_extraordinarios"></td>
                </tr>
                <tr>
                    <td><strong>Ampliación ó reducción en días naturales solicitados y autorizados:</strong></td>
                    <td><input type="number" name="dias_ampliacion"></td>
                </tr>
            </table>

            
            <div class="button-group">
                <button type="submit" class="btn-primary">Guardar Minuta</button>
                <a href="secciones/generar_pdf_minuta.php?obra_id=<?= $obra['id'] ?>&estimacion_id=<?= $estimacion_seleccionada['id'] ?>" class="btn btn-secondary">Generar PDF</a>

            </div>
        </form>
    </div>

    <script>
        // Calcular días transcurridos automáticamente
        $(document).ready(function() {
            const fechaInicioObra = "<?= $obra['fecha_inicio_contrato'] ?>"; // Formato YYYY-MM-DD
            
            $('#fechaCalculo').change(function() {
                const fechaCalculo = $(this).val();
                if (fechaInicioObra && fechaCalculo) {
                    const dias = calcularDiasTranscurridos(fechaInicioObra, fechaCalculo);
                    $('#diasResultado').text(dias);
                    $('#diasTranscurridos').val(dias);
                }
            });
        });

        function calcularDiasTranscurridos(fechaInicio, fechaFin) {
            const inicio = new Date(fechaInicio);
            const fin = new Date(fechaFin);
            const diferencia = fin - inicio;
            return Math.floor(diferencia / (1000 * 60 * 60 * 24)); // Convertir milisegundos a días
        }
    </script>
</body>
</html>