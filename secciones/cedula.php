<?php
if (!isset($obra)) {
    echo "<p>Error: No se ha cargado la información de la obra.</p>";
    return;
}
if (!isset($_SESSION['usuario_id'], $_SESSION['municipio_id'], $_SESSION['anio'])) {
    header("Location: index.php");
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

// Consulta para obtener solo la estimación seleccionada
$stmtEst = $conn->prepare("SELECT * FROM estimaciones WHERE id = ? AND obra_id = ?");
$stmtEst->execute([$estimacion_id, $obra['id']]);
$estimacion_seleccionada = $stmtEst->fetch(PDO::FETCH_ASSOC);

// Consulta para obtener todas las estimaciones (para el submenú)
$stmtAllEst = $conn->prepare("SELECT * FROM estimaciones WHERE obra_id = ?");
$stmtAllEst->execute([$obra['id']]);
$estimaciones = $stmtAllEst->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/img/logo_redondo.png" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/cedula.css">
    <title>Cédula de Estatus de Estimación</title>
</head>

<body>
    <div class="container mt-4">
        <h3 class="text-center mb-4">CEDULA DE ESTATUS DE ESTIMACIÓN</h3>
        <h5 class="text-center mb-4">(PARTICIPACIÓN DEL SUPERVISOR EXTERNO EN LA OBRA PÚBLICA)</h5>

        <form action="secciones/guardar_cedula.php" method="post">
            <input type="hidden" name="obra_id" value="<?= $obra['id'] ?>">
            <?php if ($estimacion_seleccionada): ?>
                <input type="hidden" name="estimacion_id" value="<?= $estimacion_seleccionada['id'] ?>">
            <?php endif; ?>

            <!-- DATOS GENERALES -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <strong>DATOS GENERALES</strong>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><strong>Ente fiscalizable</strong></label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($obra['municipio']) ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><strong>Fuente de financiamiento</strong></label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($obra['fuente_financiamiento']) ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><strong>Localidad</strong></label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($obra['localidad']) ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><strong>Número de obra</strong></label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($obra['nombre']) ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><strong>Descripción</strong></label>
                        <textarea class="form-control" rows="2" readonly><?= htmlspecialchars($obra['descripcion']) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- ESTIMACIÓN -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <strong>ESTIMACIÓN <?= $estimacion_seleccionada ? '#' . htmlspecialchars($estimacion_seleccionada['numero_estimacion']) : '' ?></strong>
                </div>
                <div class="card-body">
                    <?php if ($estimacion_seleccionada): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Estimación</th>
                                        <th>Periodo de estimación</th>
                                        <th>Importe C/IVA</th>
                                        <th>Amortización</th>
                                        <th>5 al millar</th>
                                        <th>Importe líquido a pagar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?= htmlspecialchars($estimacion_seleccionada['numero_estimacion']) ?></td>
                                        <td>
                                            Del <?= date('d/m/Y', strtotime($estimacion_seleccionada['fecha_del'])) ?><br>
                                            Al <?= date('d/m/Y', strtotime($estimacion_seleccionada['fecha_al'])) ?>
                                        </td>
                                        <td>$<?= number_format($estimacion_seleccionada['monto_civa'], 2) ?></td>
                                        <td>$<?= number_format($estimacion_seleccionada['amortizacion_anticipo'], 2) ?></td>
                                        <td>$<?= number_format($estimacion_seleccionada['cinco_millar'], 2) ?></td>
                                        <td>$<?= number_format($estimacion_seleccionada['liquidacion_pagar'], 2) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Selecciona una estimación del menú lateral para ver sus detalles.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ESTIMACIÓN Y SU SOPORTE -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <strong>ESTIMACIÓN Y SU SOPORTE</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="check_caratula_estimacion" id="check_caratula_estimacion">
                                <label class="form-check-label" for="check_caratula_estimacion">Carátula de estimación</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="check_resumen_partidas" id="check_resumen_partidas">
                                <label class="form-check-label" for="check_resumen_partidas">Resumen de partidas</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="check_estado_cuentas" id="check_estado_cuentas">
                                <label class="form-check-label" for="check_estado_cuentas">Estado de cuentas</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="check_estimacion" id="check_estimacion">
                                <label class="form-check-label" for="check_estimacion">Estimación</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="check_volumenes_obra" id="check_volumenes_obra">
                                <label class="form-check-label" for="check_volumenes_obra">Números generadores de volúmenes de obra ejecutada</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="check_croquis_volumenes" id="check_croquis_volumenes">
                                <label class="form-check-label" for="check_croquis_volumenes">Croquis de números generadores de volúmenes de obra ejecutada</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="check_reporte_fotografico" id="check_reporte_fotografico">
                                <label class="form-check-label" for="check_reporte_fotografico">Reporte fotográfico</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="check_pruebas_laboratorios" id="check_pruebas_laboratorios">
                                <label class="form-check-label" for="check_pruebas_laboratorios">Pruebas de laboratorios</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- OBSERVACIONES Y COMENTARIOS -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <strong>OBSERVACIONES Y/O COMENTARIOS</strong>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label><strong>Estatus de estimación</strong></label>
                        <select class="form-control" name="estatus_estimacion" required>
                            <option value="">Seleccione una opción</option>
                            <option value="aprobada">Aprobada</option>
                            <option value="no_aprobada">No Aprobada</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><strong>Descripción</strong></label>
                        <textarea class="form-control" name="observaciones" rows="4" required></textarea>
                    </div>
                </div>
            </div>

            <div class="text-center mb-4">
                <button type="submit" class="btn btn-primary btn-lg">Guardar Cédula</button>
                <a href="obras.php" class="btn btn-secondary btn-lg">Cancelar</a>
                <a href="secciones/generar_pdf_cedula.php?obra_id=<?= $obra['id'] ?>&estimacion_id=<?= $estimacion_seleccionada['id'] ?>"
                    class="btn btn-secondary btn-lg" target="_blank">Generar PDF</a>
            </div>
        </form>
    </div>
</body>

</html>