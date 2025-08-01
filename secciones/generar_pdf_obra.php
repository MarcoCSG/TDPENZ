<?php
require_once('../tcpdf/tcpdf.php');
require_once('../includes/db.php');

if (!isset($_GET['id'])) die("ID de obra no proporcionado.");

$id_obra = $_GET['id'];

$stmt = $conn->prepare("SELECT o.*, m.nombre AS municipio, m.logo_ruta FROM obras o JOIN municipios m ON o.municipio_id = m.id WHERE o.id = ?");
$stmt->execute([$id_obra]);
$obra = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$obra) die("Obra no encontrada.");

$stmtEst = $conn->prepare("SELECT * FROM estimaciones WHERE obra_id = ?");
$stmtEst->execute([$id_obra]);
$estimaciones = $stmtEst->fetchAll(PDO::FETCH_ASSOC);

// PDF personalizado
class CustomPDF extends TCPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, ''.$this->getAliasNumPage().' de '.$this->getAliasNbPages(), 0, 0, 'C');
    }
}

$pdf = new CustomPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 9);

// Logo
$logo_path = !empty($obra['logo_ruta']) ? '../' . $obra['logo_ruta'] : '../assets/img/logo2.jpg';
if (file_exists($logo_path)) {
    $pdf->Image($logo_path, 20, 15, 50);
}
$pdf->Ln(20);

// === Funciones ===
function sectionHeader($pdf, $text) {
    $pdf->SetFillColor(230, 195, 201);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(180, 7, $text, 0, 1, 'C', true);
}

function rowCenter($pdf, $label, $value) {
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->MultiCell(60, 6, $label, 1, 'C', false, 0);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->MultiCell(120, 6, $value, 1, 'C', false, 1);
}

function rowDoubleColumn($pdf, $mainLabel, $leftLabel, $leftValue, $rightLabel, $rightValue) {
    $pdf->SetFillColor(230, 195, 201);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(180, 6, $mainLabel, 0, 1, 'C', true);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(90, 5, $leftLabel, 1, 0, 'C');
    $pdf->Cell(90, 5, $rightLabel, 1, 1, 'C');
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(90, 5, $leftValue, 1, 0, 'C');
    $pdf->Cell(90, 5, $rightValue, 1, 1, 'C');
}

function rowGeorreferencia($pdf, $label, $lat, $lon) {
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(60, 12, $label, 1, 0, 'C');
    $pdf->Cell(60, 6, 'LATITUD', 1, 0, 'C');
    $pdf->Cell(60, 6, 'LONGITUD', 1, 1, 'C');
    $pdf->Cell(60, 6, '', 0, 0);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(60, 6, $lat, 1, 0, 'C');
    $pdf->Cell(60, 6, $lon, 1, 1, 'C');
}

function rowFechasContrato($pdf, $label, $firma, $inicio, $termino) {
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(40, 12, $label, 1, 0, 'C');
    $pdf->Cell(46.66, 6, 'FECHA FIRMA', 1, 0, 'C');
    $pdf->Cell(46.66, 6, 'FECHA INICIO', 1, 0, 'C');
    $pdf->Cell(46.66, 6, 'FECHA TÉRMINO', 1, 1, 'C');
    $pdf->Cell(40, 6, '', 0, 0);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(46.66, 6, $firma, 1, 0, 'C');
    $pdf->Cell(46.66, 6, $inicio, 1, 0, 'C');
    $pdf->Cell(46.66, 6, $termino, 1, 1, 'C');
}

// Función para formatear valores monetarios con manejo de nulos
function formatMoneyValue($value) {
    if ($value === null || $value === '' || strtoupper($value) === 'NA') {
        return 'N/A';
    }
    
    // Verificar si el valor es numérico
    if (is_numeric($value)) {
        return '$' . number_format(floatval($value), 2);
    }
    
    return $value; // Si no es numérico, devolver el valor original
}

// === Sección 1: Datos Generales ===
sectionHeader($pdf, 'DATOS GENERALES DE LA OBRA');
rowCenter($pdf, 'ENTE FISCALIZABLE:', $obra['municipio']);
rowCenter($pdf, 'NÚMERO DE OBRA:', $obra['numero_contrato']);
rowCenter($pdf, 'EJERCICIO FISCAL:', $obra['anio']);
rowCenter($pdf, 'DESCRIPCIÓN DE LA OBRA:', $obra['descripcion']);
rowCenter($pdf, 'FUENTE DE FINANCIAMIENTO:', $obra['fuente_financiamiento']);
rowCenter($pdf, 'LOCALIDAD:', $obra['localidad']);
rowGeorreferencia($pdf, 'GEOREFERENCIA:', $obra['latitud'], $obra['longitud']);

// === Sección 2: Contratación ===
sectionHeader($pdf, 'DATOS DE CONTRATACIÓN');
rowCenter($pdf, 'CONTRATISTA:', $obra['contratista']);
rowCenter($pdf, 'NÚMERO DE CONTRATO:', $obra['numero_contrato']);
rowCenter($pdf, 'MONTO CONTRATADO:', formatMoneyValue($obra['monto_contratado']));
rowCenter($pdf, 'ANTICIPO:', formatMoneyValue($obra['anticipo']));
rowCenter($pdf, '% DE ANTICIPO:', ($obra['porcentaje_anticipo'] !== null && is_numeric($obra['porcentaje_anticipo'])) ? $obra['porcentaje_anticipo'].'%' : 'N/A');
rowCenter($pdf, 'TIPO DE ADJUDICACIÓN:', $obra['tipo_adjudicacion']);
rowFechasContrato($pdf, 'FECHAS DE CONTRATO:', $obra['fecha_firma'], $obra['fecha_inicio_contrato'], $obra['fecha_cierre']);

// === Sección 3: Convenios ===
sectionHeader($pdf, 'DATOS DE CONVENIOS');
rowCenter($pdf, 'AMPLIACIÓN DE MONTO:', formatMoneyValue($obra['ampliacion_monto']));
rowCenter($pdf, 'AMPLIACIÓN DE PLAZO:', formatMoneyValue($obra['ampliacion_plazo']));
rowCenter($pdf, 'REDUCCIÓN DE MONTO:', formatMoneyValue($obra['reduccion_monto']));
rowCenter($pdf, 'REDUCCIÓN DE PLAZO:', formatMoneyValue($obra['reduccion_plazo']));
rowCenter($pdf, 'DIFERIMIENTO DE PERIODO CONTRACTUAL:', formatMoneyValue($obra['diferimiento_periodo']));

// === Sección 4: Estimaciones ===
sectionHeader($pdf, 'DATOS DE ESTIMACIONES');
$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell(30, 6, 'N° EST.', 1);
$pdf->Cell(20, 6, 'DEL', 1);
$pdf->Cell(20, 6, 'AL', 1);
$pdf->Cell(25, 6, 'MONTO C/IVA', 1);
$pdf->Cell(25, 6, '5 AL MILLAR', 1);
$pdf->Cell(25, 6, 'AMORT. ANT.', 1);
$pdf->Cell(35, 6, 'LIQ. PAGAR', 1);
$pdf->Ln();

$pdf->SetFont('helvetica', '', 7);
foreach ($estimaciones as $est) {
    $pdf->Cell(30, 6, $est['numero_estimacion'], 1);
    $pdf->Cell(20, 6, $est['fecha_del'], 1);
    $pdf->Cell(20, 6, $est['fecha_al'], 1);
    $pdf->Cell(25, 6, '$'.number_format($est['monto_civa'], 2), 1);
    $pdf->Cell(25, 6, '$'.number_format($est['cinco_millar'], 2), 1);
    $pdf->Cell(25, 6, '$'.number_format($est['amortizacion_anticipo'], 2), 1);
    $pdf->Cell(35, 6, '$'.number_format($est['liquidacion_pagar'], 2), 1);
    $pdf->Ln();
}

// === Firma ===
$pdf->Ln(20);
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 6, $obra['nombre_supervisor'], 0, 1, 'C');
$pdf->Cell(0, 6, 'Supervisor Externo', 0, 1, 'C');

$pdf->Output('ficha_tecnica_obra.pdf', 'I');
?>
