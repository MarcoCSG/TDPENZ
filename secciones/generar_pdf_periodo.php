<?php
require_once('../tcpdf/tcpdf.php');
require_once('../includes/db.php');
require_once('../config/aws.php');
use Aws\Exception\AwsException;

// Configurar logging para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Validar parámetros
if (!isset($_GET['id_obra']) || !isset($_GET['id_periodo'])) {
    die('Parámetros faltantes');
}

$id_obra = $_GET['id_obra'];
$id_periodo = $_GET['id_periodo'];

// Obtener periodo de supervisión con manejo de errores
try {
    $stmt = $conn->prepare("SELECT * FROM periodos_supervision WHERE id = :id");
    $stmt->execute(['id' => $id_periodo]);
    $periodo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$periodo) {
        die('Periodo de supervisión no encontrado');
    }
} catch (PDOException $e) {
    die('Error al obtener periodo de supervisión: ' . $e->getMessage());
}

// Obtener datos de la obra con manejo de errores
try {
    $stmt = $conn->prepare("SELECT o.*, m.nombre AS municipio, m.logo_ruta FROM obras o JOIN municipios m ON o.municipio_id = m.id WHERE o.id = ?");
    $stmt->execute([$id_obra]);
    $obra = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$obra) {
        die('Obra no encontrada');
    }
} catch (PDOException $e) {
    die('Error al obtener datos de la obra: ' . $e->getMessage());
}

// Obtener imágenes del periodo con manejo de errores
try {
    $stmt = $conn->prepare("SELECT ruta, descripcion FROM periodos_imagenes WHERE periodo_id = :id ORDER BY id");
    $stmt->execute(['id' => $id_periodo]);
    $imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($imagenes)) {
        error_log("⚠️ No se encontraron imágenes para el periodo ID: $id_periodo");
    }
} catch (PDOException $e) {
    die('Error al obtener imágenes del periodo: ' . $e->getMessage());
}

// Obtener nombre del municipio
try {
    $municipio_id = $obra['municipio_id'];
    $stmt = $conn->prepare("SELECT nombre FROM municipios WHERE id = :id");
    $stmt->execute(['id' => $municipio_id]);
    $municipio = $stmt->fetchColumn();
} catch (PDOException $e) {
    die('Error al obtener datos del municipio: ' . $e->getMessage());
}

// === CLASE PDF PERSONALIZADA ===
class MYPDF extends TCPDF
{
    public $obra;

    public function Header()
    {
        $logo_path = !empty($this->obra['logo_ruta']) ? '../' . $this->obra['logo_ruta'] : '../assets/img/logo2.jpg';
        if (file_exists($logo_path)) {
            try {
                $this->Image($logo_path, 10, 5, 40);
            } catch (Exception $e) {
                error_log("Error al cargar el logo: " . $e->getMessage());
            }
        }
        $this->SetY(15);
    }

    public function Footer()
    {
        $this->SetY(-25);
        $this->SetFont('helvetica', '', 9);
        $this->Cell(0, 6, $this->obra['nombre_supervisor'], 0, 1, 'C');
        $this->Cell(0, 6, 'Supervisor Externo', 0, 1, 'C');
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
        $this->SetY($this->GetY() + -3);
    }
}

// === INICIAR PDF ===
$pdf = new MYPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->obra = $obra;
$pdf->SetCreator('Sistema de Obras');
$pdf->SetAuthor('Reporte Fotográfico');
$pdf->SetTitle('Reporte Fotográfico - Periodo de Supervisión');
$pdf->SetMargins(10, 20, 10);
$pdf->SetAutoPageBreak(true, 25);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

// === TABLA DE ENCABEZADO ===
$pdf->renderHeaderTable($obra, $municipio, $periodo);

// === CONFIGURACIÓN DE IMÁGENES ===
$imageWidth = 135;
$imageHeight = 90;
$gap = 5;
$rowHeight = $imageHeight + 10;
$currentImageIndex = 0;
$imageInnerPadding = 2;

foreach ($imagenes as $index => $img) {
    // Validar datos de la imagen
    if (empty($img['ruta'])) {
        error_log("❌ URL de imagen vacía para la imagen con índice: $index");
        continue;
    }

    // Configurar posición para nueva fila (2 imágenes por fila)
    if ($currentImageIndex % 2 == 0) {
        $x1 = 10;
        $x2 = $x1 + $imageWidth + $gap;
        $rowTopY = $pdf->GetY();

        if ($rowTopY + $rowHeight > $pdf->getPageHeight() - 25) {
            $pdf->AddPage();
            $pdf->renderHeaderTable($obra, $municipio, $periodo);
            $rowTopY = $pdf->GetY();
        }

        // Dibujar recuadro envolvente
        $pdf->Rect($x1 - 0, $rowTopY - 2, ($imageWidth * 2) + $gap + 2, $rowHeight + 4);
    }

    // Calcular posición X
    $isLeftImage = $currentImageIndex % 2 == 0;
    $x = $isLeftImage ? ($x1 + $imageInnerPadding) : $x2;

    // Procesar imagen
    $imageTempPath = downloadImageFromS3($img['ruta']);
    if ($imageTempPath && file_exists($imageTempPath)) {
        // Verificar que es una imagen válida
        $imageInfo = @getimagesize($imageTempPath);
        if ($imageInfo === false) {
            error_log("❌ Archivo no es una imagen válida: " . $img['ruta']);
            unlink($imageTempPath);
            $currentImageIndex++;
            continue;
        }

        try {
            // Agregar imagen al PDF con manejo de errores
            $pdf->Image($imageTempPath, $x, $rowTopY, $imageWidth, $imageHeight, '', '', '', false, 300, '', false, false, 0, false, false, true);
            
            // Descripción
            $pdf->SetXY($x, $rowTopY + $imageHeight + 2);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->MultiCell($imageWidth, 4, $img['descripcion'], 0, 'C');
        } catch (Exception $e) {
            error_log("❌ Error al agregar imagen al PDF: " . $e->getMessage());
            
            // Mostrar placeholder si falla
            renderImagePlaceholder($pdf, $x, $rowTopY, $imageWidth, $imageHeight, $img['descripcion']);
        }

        // Eliminar imagen temporal
        unlink($imageTempPath);
    } else {
        error_log("❌ No se pudo descargar imagen: " . $img['ruta']);
        renderImagePlaceholder($pdf, $x, $rowTopY, $imageWidth, $imageHeight, $img['descripcion']);
    }

    $currentImageIndex++;

    // Mover a siguiente fila si se completó
    if ($currentImageIndex % 2 == 0) {
        $pdf->SetY($rowTopY + $rowHeight + 8);
    }
}

// === FUNCIONES AUXILIARES ===

/**
 * Descarga una imagen desde S3
 */
function downloadImageFromS3($url) {
    if (empty($url)) {
        error_log("❌ URL vacía recibida");
        return false;
    }

    $parsed = parse_url($url);
    if (!isset($parsed['path'])) {
        error_log("❌ URL no válida: " . $url);
        return false;
    }

    $key = ltrim(urldecode($parsed['path']), '/');
    $s3 = getS3Client();
    $bucket = getS3Bucket();

    try {
        // Verificar si el objeto existe primero
        if (!$s3->doesObjectExist($bucket, $key)) {
            error_log("❌ Objeto no existe en S3: " . $key);
            return false;
        }

        $result = $s3->getObject([
            'Bucket' => $bucket,
            'Key'    => $key
        ]);

        $tempFile = tempnam(sys_get_temp_dir(), 'img_');
        if (file_put_contents($tempFile, $result['Body']) === false) {
            error_log("❌ No se pudo escribir en archivo temporal");
            return false;
        }

        if (!file_exists($tempFile)) {
            error_log("❌ No se pudo crear archivo temporal para: " . $key);
            return false;
        }

        return $tempFile;
    } catch (AwsException $e) {
        error_log("❌ Error descargando imagen desde S3 (" . $key . "): " . $e->getAwsErrorMessage());
        return false;
    } catch (Exception $e) {
        error_log("❌ Error general al procesar imagen (" . $key . "): " . $e->getMessage());
        return false;
    }
}

/**
 * Muestra un placeholder cuando la imagen no está disponible
 */
function renderImagePlaceholder($pdf, $x, $y, $width, $height, $description) {
    $pdf->SetXY($x, $y);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Rect($x, $y, $width, $height, 'F');
    $pdf->SetXY($x, $y + ($height/2 - 5));
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell($width, 10, 'Imagen no disponible', 0, 0, 'C');
    
    // Descripción
    $pdf->SetXY($x, $y + $height + 2);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->MultiCell($width, 4, $description, 0, 'C');
}

// === SALIDA DEL PDF ===
$pdf->Output('reporte_supervision_' . $id_periodo . '.pdf', 'I');