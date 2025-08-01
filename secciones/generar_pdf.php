<?php
require_once('../tcpdf/tcpdf.php');
require_once('../includes/db.php');
require_once('../config/aws.php'); // Asegúrate de tener configurado getS3Client() y getS3Bucket()
use Aws\Exception\AwsException;

// Configurar logging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_GET['id_obra']) || !isset($_GET['id_estimacion'])) {
    die('Parámetros faltantes');
}

$id_obra = $_GET['id_obra'];
$id_estimacion = $_GET['id_estimacion'];

// Obtener estimación
$stmt = $conn->prepare("SELECT * FROM estimaciones WHERE id = :id");
$stmt->execute(['id' => $id_estimacion]);
$estimacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$estimacion) {
    die('Estimación no encontrada');
}

// Obtener datos de la obra
$stmt = $conn->prepare("SELECT o.*, m.nombre AS municipio, m.logo_ruta FROM obras o JOIN municipios m ON o.municipio_id = m.id WHERE o.id = ?");
$stmt->execute([$id_obra]);
$obra = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$obra) {
    die('Obra no encontrada');
}

// Obtener imágenes
$stmt = $conn->prepare("SELECT ruta, descripcion FROM estimacion_imagenes WHERE estimacion_id = :id ORDER BY id");
$stmt->execute(['id' => $id_estimacion]);
$imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($imagenes)) {
    error_log("⚠️ No se encontraron imágenes para la estimación ID: $id_estimacion");
}

// Obtener nombre del municipio
$municipio_id = $obra['municipio_id'];
$stmt = $conn->prepare("SELECT nombre FROM municipios WHERE id = :id");
$stmt->execute(['id' => $municipio_id]);
$municipio = $stmt->fetchColumn();

$tipo = ucfirst(strtolower($estimacion['tipo'] ?? ''));
$numero_estimacion = htmlspecialchars($estimacion['numero_estimacion']);

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

    public function renderHeaderTable($obra, $municipio, $numero_estimacion, $tipo)
    {
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
                <td colspan="6" align="center"><b>REPORTE FOTOGRÁFICO DEL INFORME DE LA REVISIÓN DE ESTIMACIONES</b></td>
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
                <td colspan="6" align="center"><b>Estimación: ' . $numero_estimacion . '' . $tipo . '</b></td>
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
$pdf->SetTitle('Reporte Fotográfico');
$pdf->SetMargins(10, 20, 10);
$pdf->SetAutoPageBreak(true, 25);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

// === TABLA DE ENCABEZADO ===
$pdf->renderHeaderTable($obra, $municipio, $numero_estimacion, $tipo);

// === CONFIGURACIÓN DE IMÁGENES ===
$imageWidth = 135;
$imageHeight = 90;
$gap = 5; // separación horizontal entre imágenes
$padding = 4;
$rowHeight = $imageHeight + 10; // imagen + descripción + margen
$currentImageIndex = 0;

// Posición inicial
$startY = $pdf->GetY();
$imageInnerPadding = 2; // margen interno para que la imagen no esté pegada al borde

foreach ($imagenes as $index => $img) {
    // Verificar que la URL no esté vacía
    if (empty($img['ruta'])) {
        error_log("❌ URL de imagen vacía para la imagen con índice: $index");
        continue;
    }

    // Si es inicio de nueva fila (2 imágenes por fila)
    if ($currentImageIndex % 2 == 0) {
        $x1 = 10; // margen izquierdo
        $x2 = $x1 + $imageWidth + $gap;
        $rowTopY = $pdf->GetY();

        if ($rowTopY + $rowHeight > $pdf->getPageHeight() - 25) {
            $pdf->AddPage();
            $pdf->renderHeaderTable($obra, $municipio, $numero_estimacion, $tipo);
            $rowTopY = $pdf->GetY();
        }

        // Dibujar recuadro envolvente de la fila completa (2 imágenes)
        $pdf->Rect($x1 - 0, $rowTopY - 2, ($imageWidth * 2) + $gap + 2, $rowHeight + 4);
    }

    // Calcular posición X
    $isLeftImage = $currentImageIndex % 2 == 0;
    $x = $isLeftImage ? ($x1 + $imageInnerPadding) : $x2;

    // Descargar y procesar imagen
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
            // Agregar imagen al PDF
            $pdf->Image($imageTempPath, $x, $rowTopY, $imageWidth, $imageHeight, '', '', '', false, 300, '', false, false, 0, false, false, true);
            
            // Descripción
            $pdf->SetXY($x, $rowTopY + $imageHeight + 2);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->MultiCell($imageWidth, 4, $img['descripcion'], 0, 'C');
        } catch (Exception $e) {
            error_log("❌ Error al agregar imagen al PDF: " . $e->getMessage());
        }

        // Eliminar imagen temporal
        unlink($imageTempPath);
    } else {
        error_log("❌ No se pudo descargar imagen: " . $img['ruta']);
        
        // Mostrar placeholder si la imagen no está disponible
        $pdf->SetXY($x, $rowTopY);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Rect($x, $rowTopY, $imageWidth, $imageHeight, 'F');
        $pdf->SetXY($x, $rowTopY + ($imageHeight/2 - 5));
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell($imageWidth, 10, 'Imagen no disponible', 0, 0, 'C');
        
        // Descripción
        $pdf->SetXY($x, $rowTopY + $imageHeight + 2);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->MultiCell($imageWidth, 4, $img['descripcion'], 0, 'C');
    }

    $currentImageIndex++;

    // Si terminamos la fila, mover cursor a la siguiente fila
    if ($currentImageIndex % 2 == 0) {
        $pdf->SetY($rowTopY + $rowHeight + 8); // avanzar después del recuadro
    }
}

// === FUNCIÓN AUXILIAR MEJORADA ===
function downloadImageFromS3($url) {
    // Verificar que la URL no esté vacía
    if (empty($url)) {
        error_log("❌ URL vacía recibida");
        return false;
    }

    // Extraer solo la ruta relativa del archivo
    $parsed = parse_url($url);
    if (!isset($parsed['path'])) {
        error_log("❌ URL no válida: " . $url);
        return false;
    }

    // Eliminar el primer slash "/" y decodificar caracteres especiales
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

        // Verificar que el archivo temporal se creó correctamente
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

// === SALIDA DEL PDF ===
$pdf->Output('reporte_fotografico_' . $id_estimacion . '.pdf', 'I');