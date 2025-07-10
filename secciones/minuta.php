<?php
// Configuración de errores (solo para desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Manejo de sesión seguro
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexión a la base de datos
require_once __DIR__ . '/../includes/db.php';

// Verificar si $obra está definida
if (!isset($obra)) {
    die("<div class='alert alert-danger'>Error: No se ha cargado la información de la obra.</div>");
}

// Verificar sesión
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

// Obtener ID de estimación desde la URL
$estimacion_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Consulta de estimación
$stmtEst = $conn->prepare("SELECT * FROM estimaciones WHERE id = ? AND obra_id = ?");
$stmtEst->execute([$estimacion_id, $obra['id']]);
$estimacion_seleccionada = $stmtEst->fetch(PDO::FETCH_ASSOC);

if (!$estimacion_seleccionada) {
    die("<div class='alert alert-danger'>Error: Estimación no encontrada</div>");
}

// Consulta de la obra (completa)
$stmtObra = $conn->prepare("SELECT * FROM obras WHERE id = ?");
$stmtObra->execute([$obra['id']]);
$obra = $stmtObra->fetch(PDO::FETCH_ASSOC);

// Consulta para obtener la minuta si existe
$stmtMinuta = $conn->prepare("SELECT * FROM minutas_avance WHERE obra_id = ? AND estimacion_id = ?");
$stmtMinuta->execute([$obra['id'], $estimacion_id]);
$minuta = $stmtMinuta->fetch(PDO::FETCH_ASSOC);
if (!$minuta) $minuta = [];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Minuta Informativa de Avance</title>
    <link rel="stylesheet" href="assets/css/minuta.css">
    <link rel="icon" href="assets/img/logo_redondo.png" type="image/x-icon">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <div class="form-container">

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
                <tr><th colspan="2">DATOS DE AVANCE</th></tr>

                <tr>
                    <td><strong>Avance físico general:</strong></td>
                    <td><input type="text" name="avance_fisico" value="<?= htmlspecialchars($minuta['avance_fisico'] ?? '') ?>"></td>
                </tr>

                <tr>
                    <td><strong>Avance financiero general:</strong></td>
                    <td><input type="text" name="avance_financiero" value="<?= htmlspecialchars($minuta['avance_financiero'] ?? '') ?>"></td>
                </tr>

                <tr>
                    <td><strong>Conceptos del contrato:</strong></td>
                    <td><input type="number" name="conceptos_contrato" value="<?= htmlspecialchars($minuta['conceptos_contrato'] ?? '') ?>"></td>
                </tr>

                <tr>
                    <td><strong>Conceptos ejecutados:</strong></td>
                    <td><input type="number" name="conceptos_ejecutados" value="<?= htmlspecialchars($minuta['conceptos_ejecutados'] ?? '') ?>"></td>
                </tr>

                <tr>
                    <td><strong>Partidas ejecutadas:</strong></td>
                    <td><input type="text" name="partidas_ejecutadas" value="<?= htmlspecialchars($minuta['partidas_ejecutadas'] ?? '') ?>"></td>
                </tr>

                <tr>
                    <td><strong>Conceptos por ejecutar:</strong></td>
                    <td><input type="number" name="conceptos_por_ejecutar" value="<?= htmlspecialchars($minuta['conceptos_por_ejecutar'] ?? '') ?>"></td>
                </tr>

                <tr>
                    <td><strong>Partidas por ejecutar:</strong></td>
                    <td><input type="text" name="partidas_por_ejecutar" value="<?= htmlspecialchars($minuta['partidas_por_ejecutar'] ?? '') ?>"></td>
                </tr>

                <tr>
                    <td><strong>Fecha de cálculo:</strong></td>
                    <td>
                        <input type="date" name="fecha_calculo" id="fechaCalculo" value="<?= htmlspecialchars($minuta['fecha_calculo'] ?? '') ?>" required>
                        <input type="hidden" name="dias_transcurridos" id="diasTranscurridos" value="<?= htmlspecialchars($minuta['dias_transcurridos'] ?? '') ?>">
                    </td>
                </tr>

                <tr>
                    <td><strong>Días transcurridos desde inicio:</strong></td>
                    <td><span id="diasResultado"><?= htmlspecialchars($minuta['dias_transcurridos'] ?? '0') ?></span></td>
                </tr>

                <tr>
                    <td><strong>Conceptos extraordinarios:</strong></td>
                    <td><input type="number" name="conceptos_extraordinarios" value="<?= htmlspecialchars($minuta['conceptos_extraordinarios'] ?? '') ?>"></td>
                </tr>

                <tr>
                    <td><strong>Días de ampliación/reducción:</strong></td>
                    <td><input type="number" name="dias_ampliacion" value="<?= htmlspecialchars($minuta['dias_ampliacion'] ?? '') ?>"></td>
                </tr>
            </table>

            <div class="button-group">
                <button type="submit" class="btn-primary"><?= $minuta ? 'Actualizar' : 'Guardar' ?> Minuta</button>
                <a href="secciones/generar_pdf_minuta.php?obra_id=<?= $obra['id'] ?>&estimacion_id=<?= $estimacion_seleccionada['id'] ?>" class="btn btn-secondary">Generar PDF</a>
            </div>
        </form>
    </div>

    <script>
        $(document).ready(function () {
            const fechaInicioObra = "<?= $obra['fecha_inicio_contrato'] ?>";

            $('#fechaCalculo').change(function () {
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
            return Math.floor(diferencia / (1000 * 60 * 60 * 24));
        }
    </script>
</body>

</html>
