<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../config/aws.php';

use Aws\Exception\AwsException;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['imagen']) || !isset($_POST['id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Solicitud inválida',
        'debug' => [
            'method' => $_SERVER['REQUEST_METHOD'],
            'files' => $_FILES,
            'post' => $_POST
        ]
    ]);
    exit;
}

$id = $_POST['id'];
$response = ['success' => false];

try {
    if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir el archivo: Código ' . $_FILES['imagen']['error']);
    }

    // Obtener info de la imagen existente
    $stmt = $conn->prepare("SELECT ruta FROM periodos_imagenes WHERE id = ?");
    $stmt->execute([$id]);
    $img = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$img) {
        throw new Exception('Imagen no encontrada en la base de datos.');
    }

    $s3 = getS3Client();
    $bucket = getS3Bucket();

    // Extraer "Key" de la URL actual (ruta S3 antigua)
    $url_antigua = $img['ruta'];
    $parsed = parse_url($url_antigua);
    $key_antigua = ltrim($parsed['path'], '/');

    // Subir la nueva imagen
    $nuevoNombre = uniqid() . '_' . basename($_FILES['imagen']['name']);
    $ruta_nueva = 'periodos_supervision/' . $nuevoNombre;

    $s3->putObject([
        'Bucket' => $bucket,
        'Key' => $ruta_nueva,
        'SourceFile' => $_FILES['imagen']['tmp_name'],
        'ContentType' => mime_content_type($_FILES['imagen']['tmp_name'])
    ]);

    $url_nueva = "https://{$bucket}.s3.{$_ENV['AWS_REGION']}.amazonaws.com/{$ruta_nueva}";

    // Eliminar la imagen anterior de S3
    if (!empty($key_antigua)) {
        try {
            $s3->deleteObject([
                'Bucket' => $bucket,
                'Key' => $key_antigua,
            ]);
        } catch (AwsException $e) {
            // No se detiene por error al borrar, solo lo registra
            error_log("No se pudo eliminar imagen antigua: " . $e->getAwsErrorMessage());
        }
    }

    // Actualizar base de datos
    $stmt = $conn->prepare("UPDATE periodos_imagenes SET ruta = ? WHERE id = ?");
    if ($stmt->execute([$url_nueva, $id])) {
        $response['success'] = true;
        $response['nuevaRuta'] = $url_nueva;
    } else {
        throw new Exception('Error al actualizar la base de datos.');
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
