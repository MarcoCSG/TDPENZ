<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar sesión y redirigir si no hay usuario logueado
if (!isset($_SESSION['usuario_id'], $_SESSION['municipio_id'], $_SESSION['anio'])) {
    header("Location: ../index.php");
    exit;
}

// Verificar si $obra está definida
if (!isset($obra)) {
    echo "<p>Error: No se ha cargado la información de la obra.</p>";
    return;
}

// Obtener datos del municipio
$municipio_id = $_SESSION['municipio_id'];
$anio = $_SESSION['anio'];

$stmt = $conn->prepare("SELECT nombre FROM municipios WHERE id = ?");
$stmt->execute([$municipio_id]);
$municipio = $stmt->fetchColumn();

if ($municipio === false) {
    echo "<p>Error: No se encontró el municipio.</p>";
    return;
}

$obra['municipio'] = $municipio;

// Obtener ID de la estimación desde la URL
$estimacion_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($estimacion_id <= 0) {
    echo "<div class='alert alert-danger'>ID de estimación no válido.</div>";
    return;
}

// Consulta para obtener la estimación seleccionada
$stmtEst = $conn->prepare("SELECT * FROM estimaciones WHERE id = ? AND obra_id = ?");
$stmtEst->execute([$estimacion_id, $obra['id']]);
$estimacion_seleccionada = $stmtEst->fetch(PDO::FETCH_ASSOC);

if (!$estimacion_seleccionada) {
    echo "<div class='alert alert-danger'>No se encontró la estimación especificada para esta obra.</div>";
    return;
}

// Consulta para obtener todas las estimaciones
$stmtAllEst = $conn->prepare("SELECT * FROM estimaciones WHERE obra_id = ?");
$stmtAllEst->execute([$obra['id']]);
$estimaciones = $stmtAllEst->fetchAll(PDO::FETCH_ASSOC);

// Obtener cédula si existe
$stmt = $conn->prepare("SELECT * FROM cedulas_estatus WHERE obra_id = ? AND estimacion_id = ?");
$stmt->execute([$obra['id'], $estimacion_seleccionada['id']]);
$cedula = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cedula) {
    $cedula = []; // Array vacío si no existe
}
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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
                                        <th>Importe de retenciones</th>
                                        <th>Importe líquido a pagar</th>
                                    </tr>
                                </thead>
                                <?php
                                $amortizacion = (float) $estimacion_seleccionada['amortizacion_anticipo'];
                                $cinco_millar = (float) $estimacion_seleccionada['cinco_millar'];
                                $monto_civa = (float) $estimacion_seleccionada['monto_civa'];

                                $importe_retenciones = $amortizacion + $cinco_millar;
                                $liquido_pagar = $monto_civa - $importe_retenciones;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($estimacion_seleccionada['numero_estimacion']) ?></td>
                                    <td>
                                        Del <?= date('d/m/Y', strtotime($estimacion_seleccionada['fecha_del'])) ?><br>
                                        Al <?= date('d/m/Y', strtotime($estimacion_seleccionada['fecha_al'])) ?>
                                    </td>
                                    <td>$<?= number_format($monto_civa, 2) ?></td>
                                    <td>$<?= number_format($amortizacion, 2) ?></td>
                                    <td>$<?= number_format($cinco_millar, 2) ?></td>
                                    <td>$<?= number_format($importe_retenciones, 2) ?></td>
                                    <td>$<?= number_format($liquido_pagar, 2) ?></td>
                                </tr>

                            </table>
                            <input type="hidden" name="importe_retenciones" value="<?= $importe_retenciones ?>">
                            <input type="hidden" name="liquido_pagar" value="<?= $liquido_pagar ?>">

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
                                <input class="form-check-input" type="checkbox" name="check_caratula_estimacion" id="check_caratula_estimacion"
                                    <?= isset($cedula['caratula_estimacion']) && $cedula['caratula_estimacion'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="check_caratula_estimacion">Carátula de estimación</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="check_resumen_partidas" id="check_resumen_partidas"
                                    <?= isset($cedula['resumen_partidas']) && $cedula['resumen_partidas'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="check_resumen_partidas">Resumen de partidas</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="check_estado_cuentas" id="check_estado_cuentas"
                                    <?= isset($cedula['estado_cuentas']) && $cedula['estado_cuentas'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="check_estado_cuentas">Estado de cuentas</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="check_estimacion" id="check_estimacion"
                                    <?= isset($cedula['estimacion_check']) && $cedula['estimacion_check'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="check_estimacion">Estimación</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="check_volumenes_obra" id="check_volumenes_obra"
                                    <?= isset($cedula['volumenes_obra']) && $cedula['volumenes_obra'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="check_volumenes_obra">Números generadores de volúmenes de obra ejecutada</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="check_croquis_volumenes" id="check_croquis_volumenes"
                                    <?= isset($cedula['croquis_volumenes']) && $cedula['croquis_volumenes'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="check_croquis_volumenes">Croquis de números generadores de volúmenes de obra ejecutada</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="check_reporte_fotografico" id="check_reporte_fotografico"
                                    <?= isset($cedula['reporte_fotografico']) && $cedula['reporte_fotografico'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="check_reporte_fotografico">Reporte fotográfico</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="check_pruebas_laboratorios" id="check_pruebas_laboratorios"
                                    <?= isset($cedula['pruebas_laboratorios']) && $cedula['pruebas_laboratorios'] ? 'checked' : '' ?>>
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
                            <option value="aprobada" <?= isset($cedula['estatus']) && $cedula['estatus'] === 'aprobada' ? 'selected' : '' ?>>Aprobada</option>
                            <option value="no_aprobada" <?= isset($cedula['estatus']) && $cedula['estatus'] === 'no_aprobada' ? 'selected' : '' ?>>No Aprobada</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><strong>Descripción</strong></label>
                        <textarea class="form-control" name="observaciones" rows="4" required><?= htmlspecialchars($cedula['observaciones'] ?? '') ?></textarea>
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

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
        <?= $_SESSION['success'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
        <?= $_SESSION['error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>



</html>