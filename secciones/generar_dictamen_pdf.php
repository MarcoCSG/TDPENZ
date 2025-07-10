<?php
require_once('../tcpdf/tcpdf.php');
require_once('../includes/db.php');

if (!isset($_GET['obra_id']) || !isset($_GET['estimacion_id'])) {
    die('Faltan parámetros');
}

$obra_id = $_GET['obra_id'];
$estimacion_id = $_GET['estimacion_id'];

// Obtener datos de la obra
$stmt = $conn->prepare("SELECT o.*, m.nombre AS municipio, m.logo_ruta FROM obras o 
                        JOIN municipios m ON o.municipio_id = m.id 
                        WHERE o.id = ?");
$stmt->execute([$obra_id]);
$obra = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener datos de la estimación
$stmt = $conn->prepare("SELECT * FROM estimaciones WHERE id = ?");
$stmt->execute([$estimacion_id]);
$estimacion = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener dictamen de procedencia
$stmt = $conn->prepare("SELECT * FROM dictamenes_procedencia WHERE obra_id = ? AND estimacion_id = ?");
$stmt->execute([$obra_id, $estimacion_id]);
$dictamen = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dictamen) {
    die('No se encontró el dictamen de procedencia');
}

class MYPDF extends TCPDF {
    public $obra;
    public $estimacion;
    public $dictamen;

    public function Header() {
        // Logo
        $logo = !empty($this->obra['logo_ruta']) ? '../' . $this->obra['logo_ruta'] : '../assets/img/logo2.jpg';
        if (file_exists($logo)) {
            $this->Image($logo, 15, 10, 40); // Tamaño ligeramente más pequeño
        }

        // Espacio a la derecha del logo
        $this->SetXY(140, 20); // Posición más baja
        $this->SetFont('helvetica', '', 9);
        $this->Cell(0, 5, 
            'DICTAMEN DIC/: ' . $this->obra['nombre'] . '/' . $this->estimacion['numero_estimacion'], 
            0, 1, 'R'
        );
        $this->SetX(140); // mantener alineación derecha
        $this->Cell(0, 5, 
            'FECHA: ' . date('d/m/Y', strtotime($this->dictamen['creado_en'])), 
            0, 1, 'R'
        );

        // Título
        $this->SetY(30); // Más espacio abajo del logo y la fecha
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 10, 'DICTAMEN DE PROCEDENCIA', 0, 1, 'C');
    }

    public function Footer()
    {
        $this->SetY(-25);
        $this->SetFont('helvetica', '', 9);
        // Firma del supervisor
        $this->Cell(0, 6, $this->obra['nombre_supervisor'], 0, 1, 'C');
        $this->Cell(0, 6, 'Supervisor Externo', 0, 1, 'C');
        // Número de página
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 6, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');
    }
}

// === Crear PDF ===
$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->obra = $obra;
$pdf->estimacion = $estimacion;
$pdf->dictamen = $dictamen;
$pdf->SetMargins(15, 40, 15);
$pdf->AddPage();

// === Calcular valores ===
$fecha_del = $estimacion['fecha_del'] ? date('d/m/Y', strtotime($estimacion['fecha_del'])) : '00/01/1900';
$fecha_al  = $estimacion['fecha_al']  ? date('d/m/Y', strtotime($estimacion['fecha_al'])) : '00/01/1900';
$monto_civa = number_format((float)$estimacion['monto_civa'], 2);
$monto_siniva = number_format((float)$estimacion['monto_civa'] / 1.16, 2);
$amortizacion = number_format((float)$estimacion['amortizacion_anticipo'], 2);
$cinco_millar = number_format((float)$estimacion['cinco_millar'], 2);
$liquido_pagar = number_format((float)$estimacion['liquidacion_pagar'], 2);
$estatus = strtoupper($dictamen['estatus']);

// === Generar HTML ===
$html = '
<style>
    table { border-collapse: collapse; width: 100%; font-size: 10pt; }
    td, th {
        border: 1px solid #000;
        text-align: center;
        vertical-align: middle;
        line-height: 3; /* Aumenta el espacio vertical */
    }
    .rojo { background-color: #f4cccc; font-weight: bold; text-align: rigth;}
    .verde { background-color: #d9ead3; font-weight: bold; }
</style>
<p><strong>OBRA:</strong> ' . htmlspecialchars($obra['nombre']) . '</p>
<p><strong>DESCRIPCIÓN:</strong> ' . htmlspecialchars($obra['descripcion']) . '</p>

<p style="text-align:justify;">
En base al artículo 112 fracción X, 118 VII y 111 del Reglamento de la Ley de Obras Públicas y Servicios Relacionados Con Ellas del Estado de Veracruz de Ignacio de la Llave, así mismo, en base al análisis, revisión de campo y revisión de gabinete se dictamina lo siguiente:
</p>

<table>
    <tr>
        <td class="rojo">Fecha de revisión de campo:</td>
        <td>' . date('d/m/Y', strtotime($dictamen['fecha_revision_campo'])) . '</td>
    </tr>
    <tr>
        <td class="rojo">Fecha de revisión de gabinete:</td>
        <td>' . date('d/m/Y', strtotime($dictamen['fecha_revision_gabinete'])) . '</td>
    </tr>
    <tr>
        <td class="rojo">Periodo:</td>
        <td>
            <table style="width:100%; border:none;">
                <tr style="border:none;">
                    <td style="border:none;"><strong>De</strong></td>
                    <td style="border:none;"><strong>A</strong></td>
                </tr>
                <tr style="border:none;">
                    <td style="border:none;">' . $fecha_del . '</td>
                    <td style="border:none;">' . $fecha_al . '</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr><td class="rojo">Estimación:</td><td>' . htmlspecialchars($estimacion['numero_estimacion']) . '</td></tr>
    <tr><td class="rojo">Monto C/ I.V.A.:</td><td>$' . $monto_civa . '</td></tr>
    <tr><td class="rojo">Monto S/ I.V.A.:</td><td>$' . $monto_siniva . '</td></tr>
    <tr><td class="rojo">Amortización de anticipo:</td><td>$' . $amortizacion . '</td></tr>
    <tr><td class="rojo">Retención 5 al millar:</td><td>$' . $cinco_millar . '</td></tr>
    <tr><td class="rojo">Total de deducciones:</td><td>$0.00</td></tr>
    <tr><td class="rojo">Líquido a pagar:</td><td>$' . $liquido_pagar . '</td></tr>
    <tr><td class="rojo">ESTATUS:</td><td class="verde">' . $estatus . '</td></tr>
</table>

<p><strong>OBSERVACIONES:</strong><br>' . nl2br(htmlspecialchars($dictamen['observaciones'])) . '</p>
';


$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('dictamen_procedencia_' . $obra_id . '_' . $estimacion_id . '.pdf', 'I');
