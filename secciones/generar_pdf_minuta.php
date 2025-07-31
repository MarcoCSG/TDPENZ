<?php
require_once('../tcpdf/tcpdf.php');
require_once('../includes/db.php');

if (!isset($_GET['obra_id']) || !isset($_GET['estimacion_id'])) {
    die('Parámetros faltantes');
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

// Obtener minuta de avance
$stmt = $conn->prepare("SELECT * FROM minutas_avance WHERE estimacion_id = ? AND obra_id = ?");
$stmt->execute([$estimacion_id, $obra_id]);
$minuta = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$minuta) {
    die('Minuta no encontrada.');
}

// Clase TCPDF personalizada
class MYPDF extends TCPDF
{
    public $obra;

    public function Header()
    {
        $logo = !empty($this->obra['logo_ruta']) ? '../' . $this->obra['logo_ruta'] : '../assets/img/logo2.jpg';
        if (file_exists($logo)) {
            $this->Image($logo, 15, 10, 25);
        }

        $this->SetY(10);
        // $this->SetFont('helvetica', 'B', 11);
        // $this->Cell(0, 10, 'GRUPO PENZ S.A. de C.V.', 0, 1, 'C');

        $this->SetFont('helvetica', '', 10);
        // Primera línea: Estimación y Obra
        $this->Cell(0, 5, 
            'MINUTA: MIN/' . $this->obra['nombre'] .'/' .  $this->obra['estimacion'], 
            0, 1, 'R'
        );
        // Segunda línea: Fecha
        $this->Cell(0, 5, 
            'FECHA: ' . date('d/m/Y'), 
            0, 1, 'R'
        );
        $this->Ln(5);


        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'MINUTA INFORMATIVA DE AVANCE FÍSICO Y FINANCIERO', 0, 1, 'C');
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

$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$obra['estimacion'] = $estimacion['numero_estimacion'];
$pdf->obra = $obra;
$pdf->SetMargins(15, 40, 15);
$pdf->AddPage();

$html = '
<style>
    .tabla { border-collapse: collapse; width: 100%; }
    .tabla td, .tabla th { border: 1px solid #000; padding: 5px; }
    .rojo { background-color: #f4cccc; font-weight: bold; }
</style>

<table>
    <tr><td><strong>OBRA:</strong> ' . htmlspecialchars($obra['nombre']) . '</td></tr>
    <tr><td><strong>DESCRIPCIÓN:</strong> ' . htmlspecialchars($obra['descripcion']) . '</td></tr>
</table>
<br>
<p style="font-size:10pt; text-align:justify;">
    Se apertura la presente minuta informativa en base a las facultades que nos confieren los términos de referencia 
    y de acuerdo con el artículo 114, 113 inciso a) y 112 fracción XII del Reglamento de la Ley de Obras Públicas y 
    Servicios Relacionados con Ellas del Estado de Veracruz de Ignacio de la Llave. En base al análisis realizado 
    en la cédula de avance físico y financiero de la obra podemos determinar lo siguiente:
</p>
<br>

<table class="tabla">
<tr>
    <td class="rojo">Avance físico general:</td>
    <td style="text-align:center;">' . $minuta['avance_fisico'] . '%</td>
</tr>
<tr>
    <td class="rojo">Avance financiero general:</td>
    <td style="text-align:center;">' . $minuta['avance_financiero'] . '%</td>
</tr>
    <tr><td class="rojo">Número de conceptos que forman parte de los alcances del contrato:</td><td style="text-align:center;">' . $minuta['conceptos_contrato'] . '</td></tr>
    <tr><td class="rojo">Número de conceptos ejecutados a la fecha:</td><td style="text-align:center;">' . $minuta['conceptos_ejecutados'] . '</td></tr>
    <tr><td class="rojo">Partidas ejecutadas:</td><td style="text-align:center;">' . htmlspecialchars($minuta['partidas_ejecutadas']) . '</td></tr>
    <tr><td class="rojo">Número de conceptos por ejecutar:</td><td style="text-align:center;">' . $minuta['conceptos_por_ejecutar'] . '</td></tr>
    <tr><td class="rojo">Partidas por ejecutar:</td><td style="text-align:center;">' . htmlspecialchars($minuta['partidas_por_ejecutar']) . '</td></tr>
    <tr><td class="rojo">Días transcurridos desde el inicio de los trabajos:</td><td style="text-align:center;">' . $minuta['dias_transcurridos'] . '</td></tr>
    <tr><td class="rojo">Número de conceptos extraordinarios solicitados y autorizados:</td><td style="text-align:center;">' . $minuta['conceptos_extraordinarios'] . '</td></tr>
    <tr><td class="rojo">Ampliación o reducción en días naturales solicitados y autorizados:</td><td style="text-align:center;">' . $minuta['dias_ampliacion'] . '</td></tr>
</table>
';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('minuta_informativa_' . $obra_id . '_' . $estimacion_id . '.pdf', 'I');
