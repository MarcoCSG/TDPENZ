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

// Verificar sesión de usuario
if (!isset($_SESSION['usuario_id'], $_SESSION['municipio_id'], $_SESSION['anio'])) {
    header("Location: ../index.php");
    exit;
}

$municipio_id = $_SESSION['municipio_id'];
$anio = $_SESSION['anio'];

// Obtener nombre del municipio
$stmt = $conn->prepare("SELECT nombre FROM municipios WHERE id = ?");
$stmt->execute([$municipio_id]);
$municipio = $stmt->fetchColumn();

// Obtener ID de estimación desde la URL
$estimacion_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Obtener estimación
$stmt = $conn->prepare("SELECT * FROM estimaciones WHERE id = ?");
$stmt->execute([$estimacion_id]);
$estimacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$estimacion) {
    die("<div class='alert alert-danger'>Error: No se encontró la estimación con ID $estimacion_id.</div>");
}

$id_obra = $estimacion['obra_id']; // Obtener ID de la obra desde la estimación

// Obtener datos de la obra
$stmt = $conn->prepare("SELECT * FROM obras WHERE id = ?");
$stmt->execute([$id_obra]);
$obra = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$obra) {
    die("<div class='alert alert-danger'>Error: No se encontró la obra con ID $id_obra.</div>");
}

$obra['municipio'] = $municipio;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dictamen de Procedencia</title>
    <link rel="stylesheet" href="assets/css/dictamen.css">

</head>
<body>
    <div class="container">
        <div class="card">
            <h2>DICTAMEN DE PROCEDENCIA</h2>
            <form action="guardar_dictamen.php" method="POST">
                <input type="hidden" name="obra_id" value="<?= $id_obra ?>">
                <input type="hidden" name="estimacion_id" value="<?= $estimacion_id ?>">

                <div class="form-group">
                    <label>OBRA:</label>
                    <input type="text" value="<?= htmlspecialchars($obra['nombre']) ?>" readonly>
                </div>

                <div class="form-group">
                    <label>DESCRIPCIÓN:</label>
                    <textarea readonly><?= htmlspecialchars($obra['descripcion']) ?></textarea>
                </div>

                <div class="form-group">
                    <label>Fecha de revisión de campo:</label>
                    <input type="date" name="fecha_revision_campo" required>
                </div>

                <div class="form-group">
                    <label>Fecha de revisión de gabinete:</label>
                    <input type="date" name="fecha_revision_gabinete" required>
                </div>

                <table>
                    <tr><th colspan="2">Datos de la Estimación</th></tr>
                    <tr>
                        <td>Periodo:</td>
                        <td>
                            Del <?= $estimacion['fecha_del'] ? date('d/m/Y', strtotime($estimacion['fecha_del'])) : 'Sin fecha' ?>
                            al <?= $estimacion['fecha_al'] ? date('d/m/Y', strtotime($estimacion['fecha_al'])) : 'Sin fecha' ?>
                        </td>
                    </tr>
                    <tr><td>Número de estimación:</td><td><?= htmlspecialchars($estimacion['numero_estimacion']) ?></td></tr>
                    <tr><td>Monto con I.V.A:</td><td>$<?= number_format((float)$estimacion['monto_civa'], 2) ?></td></tr>
                    <tr><td>Monto sin I.V.A:</td><td>$<?= number_format((float)$estimacion['monto_civa'] / 1.16, 2) ?></td></tr>
                    <tr><td>Amortización de anticipo:</td><td>$<?= number_format((float)$estimacion['amortizacion_anticipo'], 2) ?></td></tr>
                    <tr><td>Retención 5 al millar:</td><td>$<?= number_format((float)$estimacion['cinco_millar'], 2) ?></td></tr>
                    <tr><td>Total de deducciones:</td><td>$<?= number_format((float)$estimacion['total_deducciones'], 2) ?></td></tr>
                    <tr><td>Líquido a pagar:</td><td>$<?= number_format((float)$estimacion['liquidacion_pagar'], 2) ?></td></tr>
                </table>

                <div class="form-group">
                    <label>Estatus del dictamen:</label>
                    <select name="estatus" required>
                        <option value="">Seleccionar</option>
                        <option value="PROCEDENTE">PROCEDENTE</option>
                        <option value="NO PROCEDENTE">NO PROCEDENTE</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Observaciones:</label>
                    <textarea name="observaciones" rows="4" placeholder="Observaciones..."></textarea>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn-primary">Guardar Dictamen</button>
                    <a href="generar_dictamen_pdf.php?id=<?= $estimacion_id ?>" class="btn-secondary">Generar PDF</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
