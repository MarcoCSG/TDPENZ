<?php
require_once('../tcpdf/tcpdf.php');
require_once('../includes/db.php');
require_once('../config/aws.php'); // Asegúrate de tener configurado getS3Client() y getS3Bucket()
use Aws\Exception\AwsException;


if (!isset($_GET['id_obra']) || !isset($_GET['id_periodo'])) {
    die('Parámetros faltantes');
}

$id_obra = $_GET['id_obra'];
$id_periodo = $_GET['id_periodo'];

// Obtener periodo de supervisión
$stmt = $conn->prepare("SELECT * FROM periodos_supervision WHERE id = :id");
$stmt->execute(['id' => $id_periodo]);
$periodo = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener datos de la obra
$stmt = $conn->prepare("SELECT o.*, m.nombre AS municipio, m.logo_ruta FROM obras o JOIN municipios m ON o.municipio_id = m.id WHERE o.id = ?");
$stmt->execute([$id_obra]);
$obra = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener imágenes (asumiendo que usas la misma tabla de imágenes o necesitas crear una para periodos)
$stmt = $conn->prepare("SELECT ruta, descripcion FROM periodos_imagenes WHERE periodo_id = :id");
$stmt->execute(['id' => $id_periodo]); // Ajustar según tu estructura real
$imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener nombre del municipio
$municipio_id = $obra['municipio_id'];
$stmt = $conn->prepare("SELECT nombre FROM municipios WHERE id = :id");
$stmt->execute(['id' => $municipio_id]);
$municipio = $stmt->fetchColumn();

// === CLASE PDF PERSONALIZADA ===
class MYPDF extends TCPDF
{
    public $obra;

    public function Header()
    {
        $logo_path = !empty($this->obra['logo_ruta']) ? '../' . $this->obra['logo_ruta'] : '../assets/img/logo2.jpg';
        if (file_exists($logo_path)) {
            $this->Image($logo_path, 10, 5, 40); // Logo en la esquina superior izquierda
        }
        $this->SetY(15); // Espacio inferior del header
    }

    public function Footer()
    {
        $this->SetY(-25);
        $this->SetFont('helvetica', '', 9);
        // Firma del supervisor (centrada)
        $this->Cell(0, 6, $this->obra['nombre_supervisor'], 0, 1, 'C');
        $this->Cell(0, 6, 'Supervisor Externo', 0, 1, 'C');
        // Número de página (derecha)
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 6, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');
    }

    public function renderHeaderTable($obra, $municipio, $periodo)
    {
        $fecha_inicio = date('d/m/Y', strtotime($periodo['fecha_inicio']));
        $fecha_fin = date('d/m/Y', strtotime($periodo['fecha_fin']));

        $html = '
        <style>
            table.header-table { border-collapse: collapse; margin-bottom: 0; }
            table.header-table td { vertical-align: middle; padding: 3px; }
            .header-bg { background-color: #f4cccc; }
        </style>
        <table class="header-table" border="1" cellpadding="2">
            <tr class="header-bg">
                <td colspan="6" align="center"><b>REPORTE FOTOGRÁFICO (ANEXO II)</b></td>
            </tr>
            <tr class="header-bg">
                <td colspan="6" align="center"><b>REPORTE FOTOGRÁFICO DEL INFORME DE SEGUIMIENTO DE OBRA PÚBLICA (PARTICIPACIÓN DEL SUPERVISOR EXTERNO EN LA OBRA PÚBLICA)</b></td>
            </tr>
            <tr class="header-bg">
                <td colspan="6" align="center"><b>DATOS GENERALES</b></td>
            </tr>
            <tr>
                <td width="20%"><b>Ente fiscalizable</b></td>
                <td width="30%">' . htmlspecialchars($municipio) . '</td>
                <td width="20%"><b>Fuente de financiamiento</b></td>
                <td width="30%" colspan="3">' . nl2br(htmlspecialchars($obra['fuente_financiamiento'])) . '</td>
            </tr>
            <tr>
                <td><b>Localidad</b></td>
                <td>' . htmlspecialchars($obra['localidad']) . '</td>
                <td><b>Número de obra</b></td>
                <td colspan="3">' . htmlspecialchars($obra['nombre']) . '</td>
            </tr>
            <tr>
                <td><b>Descripción</b></td>
                <td colspan="5">' . htmlspecialchars($obra['descripcion']) . '</td>
            </tr>
            <tr class="header-bg">
                <td colspan="6" align="center"><b>Periodo de Supervisión: ' . htmlspecialchars($periodo['mes']) . '</b></td>
            </tr>
            <tr>
                <td><b>Fecha de inicio</b></td>
                <td>' . $fecha_inicio . '</td>
                <td><b>Fecha de fin</b></td>
                <td colspan="3">' . $fecha_fin . '</td>
            </tr>
        </table>';
        $this->writeHTML($html, true, false, true, false, '');
        $this->SetY($this->GetY() + -3); // Espacio debajo de la tabla
    }
}

// === INICIAR PDF ===
$pdf = new MYPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->obra = $obra;
$pdf->SetCreator('Sistema de Obras');
$pdf->SetAuthor('Reporte Fotográfico');
$pdf->SetTitle('Reporte Fotográfico - Periodo de Supervisión');
$pdf->SetMargins(10, 20, 10); // Margen superior modificado (para logo)
$pdf->SetAutoPageBreak(true, 25); // Margen inferior modificado (para firma)
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

// === TABLA DE ENCABEZADO ===
$pdf->renderHeaderTable($obra, $municipio, $periodo);

// === CONFIGURACIÓN DE IMÁGENES ===
$imageWidth = 135;
$imageHeight = 90;
$gap = 5; // separación horizontal entre imágenes
$padding = 4;
$rowHeight = $imageHeight + 10; // imagen + descripción + margen
$currentImageIndex = 10;

// Posición inicial
$startY = $pdf->GetY();

$imageInnerPadding = 2; // margen interno para que la imagen no esté pegada al borde

foreach ($imagenes as $index => $img) {
    // Si es inicio de nueva fila (2 imágenes por fila)
    if ($currentImageIndex % 2 == 0) {
        $x1 = 10; // margen izquierdo
        $x2 = $x1 + $imageWidth + $gap;
        $rowTopY = $pdf->GetY();

        // Verifica si la fila cabe en la página
        if ($rowTopY + $rowHeight > $pdf->getPageHeight() - 25) {
            $pdf->AddPage();
            $pdf->renderHeaderTable($obra, $municipio, $periodo);
            $rowTopY = $pdf->GetY();
        }

        // Dibujar recuadro envolvente de la fila completa (2 imágenes)
        $pdf->Rect($x1 - 0, $rowTopY - 2, ($imageWidth * 2) + $gap + 2, $rowHeight + 4);
    }

    // Calcular posición X
    $isLeftImage = $currentImageIndex % 2 == 0;
    $x = $isLeftImage ? ($x1 + $imageInnerPadding) : $x2;

    // Dibujar imagen
    $imageTempPath = downloadImageFromS3($img['ruta']);
    if ($imageTempPath && file_exists($imageTempPath)) {
        $pdf->Image($imageTempPath, $x, $rowTopY, $imageWidth, $imageHeight);

        // Descripción
        $pdf->SetXY($x, $rowTopY + $imageHeight + 2);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->MultiCell($imageWidth, 4, $img['descripcion'], 0, 'C');

        // Eliminar imagen temporal
        unlink($imageTempPath);
    }


    $currentImageIndex++;

    // Si terminamos la fila, mover cursor a la siguiente fila
    if ($currentImageIndex % 2 == 0) {
        $pdf->SetY($rowTopY + $rowHeight + 8); // avanzar después del recuadro
    }
}

// === SALIDA DEL PDF ===
$pdf->Output('reporte_supervision.pdf', 'I');

// === FUNCIÓN AUXILIAR ===
    function downloadImageFromS3($url)
    {
        $parsed = parse_url($url);
        if (!isset($parsed['path'])) return false;

        $key = ltrim($parsed['path'], '/');
        $s3 = getS3Client();
        $bucket = getS3Bucket();

        try {
            $result = $s3->getObject([
                'Bucket' => $bucket,
                'Key'    => $key
            ]);

            $tempFile = tempnam(sys_get_temp_dir(), 'img_');
            file_put_contents($tempFile, $result['Body']);
            return $tempFile;
        } catch (AwsException $e) {
            error_log("Error descargando imagen desde S3: " . $e->getAwsErrorMessage());
            return false;
        }
    }
