<?php
if (!isset($obra)) {
    echo "<p>Error: No se ha cargado la información de la obra.</p>";
    return;
}
if (!isset($_SESSION['usuario_id'], $_SESSION['municipio_id'], $_SESSION['anio'])) {
    header("Location: index.php");
    exit;
}
// Solo usar variables de sesión, nunca POST
$municipio_id = $_SESSION['municipio_id'];
$anio = $_SESSION['anio'];

// Obtener nombre del municipio seleccionado
$stmt = $conn->prepare("SELECT nombre FROM municipios WHERE id = ?");
$stmt->execute([$municipio_id]);
$municipio = $stmt->fetchColumn();
$obra['municipio'] = $municipio;

//estimaciones
$stmtEst = $conn->prepare("SELECT * FROM estimaciones WHERE obra_id = ?");
$stmtEst->execute([$obra['id']]);
$estimaciones = $stmtEst->fetchAll(PDO::FETCH_ASSOC);

// periodos de supervisión
$stmtPer = $conn->prepare("SELECT * FROM periodos_supervision WHERE obra_id = ?");
$stmtPer->execute([$obra['id']]);
$periodos = $stmtPer->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/img/logo_redondo.png" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/general.css">
    <title>Document</title>
</head>
<div class="container">
    <h2 class="section-title">Datos Generales de la Obra</h2>
    <div class="data-grid">
        <div class="label">Nombre:</div>
        <div class="value"><?= htmlspecialchars($obra['nombre']) ?></div>

        <div class="label">Descripción:</div>
        <div class="value"><?= htmlspecialchars($obra['descripcion']) ?></div>

        <div class="label">Fuente de Financiamiento:</div>
        <div class="value"><?= htmlspecialchars($obra['fuente_financiamiento']) ?></div>

        <div class="label">Localidad:</div>
        <div class="value"><?= htmlspecialchars($obra['localidad']) ?></div>

        <div class="label">Georreferencia:</div>
        <div class="value">Lat: <?= $obra['latitud'] ?>, Long: <?= $obra['longitud'] ?></div>

        <div class="label">Municipio:</div>
        <div class="value"><?= htmlspecialchars($obra['municipio']) ?? '' ?></div>

        <div class="label">Año Fiscal:</div>
        <div class="value"><?= htmlspecialchars($obra['anio']) ?></div>
    </div>

    <h2 class="section-title">Datos de Contratación</h2>
    <div class="data-grid">
        <div class="label">Contratista:</div>
        <div class="value"><?= htmlspecialchars($obra['contratista']) ?></div>

        <div class="label">Número de Contrato:</div>
        <div class="value"><?= htmlspecialchars($obra['numero_contrato']) ?></div>

        <div class="label">Monto Contratado:</div>
        <div class="value">$<?= number_format($obra['monto_contratado'], 2) ?></div>

        <div class="label">Anticipo (%):</div>
        <div class="value"><?= htmlspecialchars($obra['porcentaje_anticipo']) ?>%</div>

        <div class="label">Anticipo Total:</div>
        <div class="value">$<?= number_format($obra['anticipo'], 2) ?></div>

        <div class="label">Tipo de Adjudicación:</div>
        <div class="value"><?= htmlspecialchars($obra['tipo_adjudicacion']) ?></div>

        <div class="label">Fecha de Firma:</div>
        <div class="value"><?= htmlspecialchars($obra['fecha_firma']) ?></div>

        <div class="label">Fecha de Inicio:</div>
        <div class="value"><?= htmlspecialchars($obra['fecha_inicio_contrato']) ?></div>

        <div class="label">Fecha de Cierre:</div>
        <div class="value"><?= htmlspecialchars($obra['fecha_cierre']) ?></div>
    </div>

    <h2 class="section-title">Datos de Convenios</h2>
    <div class="data-grid">
        <div class="label">Ampliación de Monto:</div>
        <div class="value">$<?= number_format($obra['ampliacion_monto'], 2) ?></div>

        <div class="label">Reducción de Monto:</div>
        <div class="value">$<?= number_format($obra['reduccion_monto'], 2) ?></div>

        <div class="label">Ampliación de Plazo:</div>
        <div class="value"><?= htmlspecialchars($obra['ampliacion_plazo']) ?></div>

        <div class="label">Reducción de Plazo:</div>
        <div class="value"><?= htmlspecialchars($obra['reduccion_plazo']) ?></div>

        <div class="label">Diferimiento de Periodo:</div>
        <div class="value"><?= htmlspecialchars($obra['diferimiento_periodo']) ?></div>
    </div>

    <h2 class="section-title">Estimaciones</h2>
    <?php if (count($estimaciones) > 0): ?>
        <div class="data-grid">
            <?php foreach ($estimaciones as $est): ?>
                <div class="label">No. Estimación:</div>
                <div class="value"><?= htmlspecialchars($est['numero_estimacion']) ?></div>

                <div class="label">Fecha del:</div>
                <div class="value"><?= htmlspecialchars($est['fecha_del']) ?></div>

                <div class="label">Fecha al:</div>
                <div class="value"><?= htmlspecialchars($est['fecha_al']) ?></div>

                <div class="label">Monto:</div>
                <div class="value">$<?= number_format($est['monto_civa'], 2) ?></div>

                <div class="label">Cinco al millar:</div>
                <div class="value">$<?= number_format($est['cinco_millar'], 2) ?></div>

                <div class="label">Amort. Ant.:</div>
                <div class="value">$<?= number_format($est['amortizacion_anticipo'], 2) ?></div>

                <div class="label">Liq. Pagar:</div>
                <div class="value">$<?= number_format($est['liquidacion_pagar'], 2) ?></div>

                <hr style="grid-column: 1 / -1; margin: 1rem 0;">
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No hay estimaciones registradas.</p>
    <?php endif; ?>

    <h2 class="section-title">Periodos de Supervisión</h2>
    <?php if (count($periodos) > 0): ?>
        <div class="data-grid">
            <?php foreach ($periodos as $per): ?>
                <div class="label">Mes:</div>
                <div class="value"><?= htmlspecialchars($per['mes']) ?></div>

                <div class="label">Fecha del:</div>
                <div class="value"><?= htmlspecialchars($per['fecha_inicio']) ?></div>

                <div class="label">Fecha al:</div>
                <div class="value"><?= htmlspecialchars($per['fecha_fin']) ?></div>
                <hr style="grid-column: 1 / -1; margin: 1rem 0;">
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No hay periodos de supervisión registrados.</p>
    <?php endif; ?>


    <div class="button-group">
        <a href="editar_obra.php?id=<?= $obra['id'] ?>" class="btn btn-primary">Editar</a>
        <a href="supervision_obras.php" class="btn btn-outline">Volver al listado</a>
        <a href="generar_pdf_obra.php?id=<?= $obra['id'] ?>" class="btn btn-danger" target="_blank">Descargar PDF</a>
    </div>
</div>