<?php
session_start();
require_once('../includes/db.php');
require_once('../tcpdf/tcpdf.php');

if (!isset($_GET['id'])) {
    die('ID de estimación no proporcionado');
}

$estimacion_id = $_GET['id'];
$obra_id = $_SESSION['obra_id'];
$municipio_id = $_SESSION['municipio_id'];
$anio = $_SESSION['anio'];

// Obtener datos de la obra
$stmt = $conn->prepare("SELECT nombre, localidad, fuente_financiamiento, descripcion FROM obras WHERE id = ?");
$stmt->execute([$obra_id]);
$obra = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener nombre del municipio
$stmt = $conn->prepare("SELECT nombre FROM municipios WHERE id = ?");
$stmt->execute([$municipio_id]);
$municipio = $stmt->fetchColumn();

// Obtener datos de la estimación
$stmt = $conn->prepare("SELECT * FROM estimaciones WHERE id = ? AND obra_id = ?");
$stmt->execute([$estimacion_id, $obra_id]);
$estimacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$estimacion) {
    die('Estimación no encontrada');
}

// Obtener imágenes de la estimación
$stmt = $conn->prepare("SELECT * FROM estimacion_imagenes WHERE estimacion_id = ?");
$stmt->execute([$estimacion_id]);
$imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear nuevo documento PDF en horizontal
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

// Configuración del documento
$pdf->SetCreator('Sistema de Estimaciones');
$pdf->SetAuthor('Supervisor Externo');
$pdf->SetTitle('Reporte Fotográfico - Anexo II');
$pdf->SetSubject('Reporte Fotográfico');

// Establecer márgenes
$pdf->SetMargins(15, 15, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);

// Añadir página
$pdf->AddPage();

// Función para verificar y corregir rutas de imágenes
function getImagePath($ruta) {
    // Si la ruta ya es absoluta (comienza con /)
    if (strpos($ruta, '/') === 0) {
        return $_SERVER['DOCUMENT_ROOT'] . $ruta;
    }
    
    // Si es una ruta relativa
    $base_path = $_SERVER['DOCUMENT_ROOT'] . '/TDPENZ/';
    
    // Verificar si la imagen existe en uploads/estimaciones/
    if (file_exists($base_path . $ruta)) {
        return $base_path . $ruta;
    }
    
    // Verificar si existe en la ruta directa
    if (file_exists($ruta)) {
        return $ruta;
    }
    
    return false;
}

// Estilos CSS para el PDF
$style = '
<style>
    .titulo {
        font-size: 14pt;
        font-weight: bold;
        text-align: center;
        margin-bottom: 10px;
    }
    .subtitulo {
        font-size: 10pt;
        font-weight: bold;
        text-align: center;
        margin-bottom: 15px;
    }
    .tabla-datos {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    .tabla-datos td {
        padding: 5px;
        border: 1px solid #000;
    }
    .etiqueta {
        font-weight: bold;
        width: 30%;
    }
    .grid-imagenes {
        width: 100%;
        border-collapse: collapse;
    }
    .celda-imagen {
        width: 33%;
        vertical-align: top;
        padding: 5px;
    }
    .imagen-contenedor {
        text-align: center;
        margin-bottom: 15px;
    }
    .descripcion {
        font-size: 8pt;
        text-align: center;
        margin-top: 5px;
        padding: 5px;
        border: 1px solid #ddd;
    }
    .fuente-financiamiento {
        font-weight: bold;
        margin-top: 10px;
    }
    .estimacion {
        font-weight: bold;
        text-align: center;
        margin: 15px 0;
        font-size: 12pt;
    }
</style>
';

// Contenido HTML del PDF
$html = $style . '
<h1 class="titulo">REPORTE FOTOGRÁFICO (ANEXO II)</h1>
<p class="subtitulo">REPORTE FOTOGRÁFICO DEL INFORME DE LA REVISIÓN DE ESTIMACIONES<br>(PARTICIPACIÓN DEL SUPERVISOR EXTERNO EN LA OBRA PÚBLICA)</p>

<table class="tabla-datos">
    <tr>
        <td class="etiqueta">EJERCICIO FISCAL:</td>
        <td>' . $anio . '</td>
    </tr>
    <tr>
        <td class="etiqueta">ENTE FISCALIZABLE:</td>
        <td>' . htmlspecialchars($municipio) . '</td>
    </tr>
    <tr>
        <td class="etiqueta">LOCALIDAD:</td>
        <td>' . htmlspecialchars($obra['localidad']) . '</td>
    </tr>
    <tr>
        <td class="etiqueta">NÚMERO DE OBRA:</td>
        <td>' . htmlspecialchars($obra['nombre']) . '</td>
    </tr>
    <tr>
        <td class="etiqueta">DESCRIPCIÓN:</td>
        <td>' . htmlspecialchars($obra['descripcion']) . '</td>
    </tr>
</table>

<p class="fuente-financiamiento">FUENTE DE FINANCIAMIENTO: ' . htmlspecialchars($obra['fuente_financiamiento']) . '</p>

<p class="estimacion">ESTIMACIÓN: ' . htmlspecialchars($estimacion['numero_estimacion']) . ' (' . ucfirst(strtolower($estimacion['tipo'] ?? 'NORMAL')) . ')</p>
';

// Crear tabla para organizar imágenes en 3 columnas
$html .= '<table class="grid-imagenes">';
$count = 0;

foreach ($imagenes as $img) {
    if ($count % 3 == 0) {
        $html .= '<tr>';
    }
    
    $html .= '<td class="celda-imagen">';
    $html .= '<div class="imagen-contenedor">';
    
    $imagePath = getImagePath($img['ruta']);
    if ($imagePath && file_exists($imagePath)) {
        // Usar Image() para incluir la imagen correctamente
        $pdf->Image($imagePath, '', '', 80, 60, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
        $html .= '<img src="' . $img['ruta'] . '" style="max-width:80px; max-height:60px;"><br>';
    } else {
        $html .= '<div style="border:1px dashed #000; width:80px; height:60px; margin:0 auto;">Imagen no encontrada</div>';
    }
    
    $html .= '<div class="descripcion">' . htmlspecialchars($img['descripcion']) . '</div>';
    $html .= '</div>';
    $html .= '</td>';
    
    if ($count % 3 == 2) {
        $html .= '</tr>';
    }
    
    $count++;
}

// Completar fila si es necesario
if ($count % 3 != 0) {
    $html .= str_repeat('<td class="celda-imagen"></td>', 3 - ($count % 3));
    $html .= '</tr>';
}

$html .= '</table>';

// Escribir el HTML en el PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Salida del PDF
$pdf->Output('Reporte_Fotografico_' . $estimacion_id . '.pdf', 'I');
?>