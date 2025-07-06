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

// Obtener datos de la cédula de estatus
$stmt = $conn->prepare("SELECT * FROM cedulas_estatus WHERE obra_id = ? AND estimacion_id = ?");
$stmt->execute([$obra_id, $estimacion_id]);
$cedula = $stmt->fetch(PDO::FETCH_ASSOC);

class MYPDF extends TCPDF {
    public $obra;
    
    public function Header() {
        // Logo en la esquina superior izquierda
        $logo_path = !empty($this->obra['logo_ruta']) ? '../' . $this->obra['logo_ruta'] : '../assets/img/logo2.jpg';
        if (file_exists($logo_path)) {
            $this->Image($logo_path, 15, 10, 30);
        }
        
        // Título del documento
        $this->SetY(15);
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 10, 'CÉDULA DE ESTATUS DE ESTIMACIÓN', 0, 1, 'C');
        $this->SetFont('helvetica', '', 12);
        $this->Cell(0, 5, '(PARTICIPACIÓN DEL SUPERVISOR EXTERNO EN LA OBRA PÚBLICA)', 0, 1, 'C');
    }
    
    public function Footer() {
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

// Crear PDF en orientación horizontal
$pdf = new MYPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->obra = $obra;
$pdf->SetMargins(15, 30, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(15);
$pdf->AddPage();

// Estilos CSS
$style = '
<style>
    .main-table {
        width: 100%;
        border: 1px solid #000;
        border-collapse: collapse;
    }
    .main-table td, .main-table th {
        border: 1px solid #000;
        padding: 5px;
    }
    .section-title {
        background-color: #f4cccc;
        font-weight: bold;
        text-align: center;
        font-size: 12pt;
    }
    .sub-title {
        font-weight: bold;
        background-color: #f2f2f2;
    }
    .checkmark {
        font-family: dejavusans;
        width: 20px;
        text-align: center;
    }
    .left-col {
        width: 50%;
        vertical-align: top;
    }
    .right-col {
        width: 50%;
        vertical-align: top;
        border-left: 1px solid #000;
    }
    .observaciones {
        min-height: 100px;
    }
    .estatus {
        font-weight: bold;
        margin-top: 10px;
    }
    .estimacion-row {
        font-size: 10pt;
    }
    .small-col {
        width: 14.15%;
    }
    .medium-col {
        width: 15%;
    }
</style>
';

// Calcular valores si no están en la cédula
$importe_retenciones = isset($cedula['importe_retenciones']) ? $cedula['importe_retenciones'] : 
                      ($estimacion['amortizacion_anticipo'] + $estimacion['cinco_millar']);
$liquido_pagar = isset($cedula['liquido_pagar']) ? $cedula['liquido_pagar'] : 
                ($estimacion['monto_civa'] - $importe_retenciones);

// Contenido HTML - Estructura de tabla principal
$html = $style . '
<table class="main-table">
    <!-- Datos Generales -->
    <tr>
        <th colspan="6" class="section-title">DATOS GENERALES</th>
    </tr>
    <tr>
        <th class="sub-title" width="15%">Ente fiscalizable</th>
        <td width="20%">' . htmlspecialchars($obra['municipio']) . '</td>
        <th class="sub-title" width="15%">Fuente de financiamiento</th>
        <td width="20%">' . htmlspecialchars($obra['fuente_financiamiento']) . '</td>
        <th class="sub-title" width="15%">Número de obra</th>
        <td width="15%">' . htmlspecialchars($obra['nombre']) . '</td>
    </tr>
    <tr>
        <th class="sub-title">Localidad</th>
        <td>' . htmlspecialchars($obra['localidad']) . '</td>
        <th class="sub-title">Descripción</th>
        <td colspan="3">' . htmlspecialchars($obra['descripcion']) . '</td>
    </tr>
    
    <!-- Estimación -->
    <tr>
        <th colspan="7" class="section-title">ESTIMACIÓN #' . htmlspecialchars($estimacion['numero_estimacion']) . '</th>
    </tr>
    <tr>
        <th class="sub-title small-col">Estimación</th>
        <th class="sub-title medium-col">Periodo</th>
        <th class="sub-title small-col">Importe C/IVA</th>
        <th class="sub-title small-col">Amortización</th>
        <th class="sub-title small-col">5 al millar</th>
        <th class="sub-title small-col">Retenciones</th>
        <th class="sub-title small-col">Líquido a pagar</th>
    </tr>
    <tr class="estimacion-row">
        <td>' . htmlspecialchars($estimacion['numero_estimacion']) . '</td>
        <td>Del ' . date('d/m/Y', strtotime($estimacion['fecha_del'])) . '<br>Al ' . date('d/m/Y', strtotime($estimacion['fecha_al'])) . '</td>
        <td>$' . number_format($estimacion['monto_civa'], 2) . '</td>
        <td>$' . number_format($estimacion['amortizacion_anticipo'], 2) . '</td>
        <td>$' . number_format($estimacion['cinco_millar'], 2) . '</td>
        <td>$' . number_format($importe_retenciones, 2) . '</td>
        <td>$' . number_format($liquido_pagar, 2) . '</td>
    </tr>
    
    <!-- Estimación y su Soporte -->
    <tr>
        <th colspan="7" class="section-title">ESTIMACIÓN Y SU SOPORTE</th>
    </tr>
    <tr>
        <td colspan="4" class="left-col">
            <table width="150%">
                <tr>
                    <td class="checkmark">' . ($cedula['caratula_estimacion'] ? '✓' : '') . '</td>
                    <td>Carátula de estimación</td>
                </tr>
                <tr>
                    <td class="checkmark">' . ($cedula['resumen_partidas'] ? '✓' : '') . '</td>
                    <td>Resumen de partidas</td>
                </tr>
                <tr>
                    <td class="checkmark">' . ($cedula['estado_cuentas'] ? '✓' : '') . '</td>
                    <td>Estado de cuenta</td>
                </tr>
                <tr>
                    <td class="checkmark">' . ($cedula['estimacion_check'] ? '✓' : '') . '</td>
                    <td>Estimación</td>
                </tr>
                <tr>
                    <td class="checkmark">' . ($cedula['volumenes_obra'] ? '✓' : '') . '</td>
                    <td>Números generadores de volúmenes</td>
                </tr>
                <tr>
                    <td class="checkmark">' . ($cedula['croquis_volumenes'] ? '✓' : '') . '</td>
                    <td>Croquis de números generadores</td>
                </tr>
                <tr>
                    <td class="checkmark">' . ($cedula['reporte_fotografico'] ? '✓' : '') . '</td>
                    <td>Reporte fotográfico</td>
                </tr>
                <tr>
                    <td class="checkmark">' . ($cedula['pruebas_laboratorios'] ? '✓' : '') . '</td>
                    <td>Pruebas de laboratorio</td>
                </tr>
            </table>
            
            <div class="estatus">ESTATUS DE ESTIMACIÓN: ' . strtoupper($cedula['estatus']) . '</div>
        </td>
        <td colspan="3" class="right-col">
            <div class="observaciones">
                <strong>OBSERVACIONES Y/O COMENTARIOS:</strong><br><br>
                ' . nl2br(htmlspecialchars($cedula['observaciones'])) . '
            </div>
        </td>
    </tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('cedula_estatus_' . $obra_id . '_' . $estimacion_id . '.pdf', 'I');