<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../config/aws.php';

use Aws\Exception\AwsException;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];

    try {
        // 1. Obtener la URL de la imagen
        $stmt = $conn->prepare("SELECT ruta FROM periodos_imagenes WHERE id = ?");
        $stmt->execute([$id]);
        $imagen = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$imagen || empty($imagen['ruta'])) {
            throw new Exception('Imagen no encontrada en la base de datos');
        }

        $s3 = getS3Client();
        $bucket = getS3Bucket();

        // 2. Obtener la "Key" desde la URL
        $url = $imagen['ruta'];
        $parsed = parse_url($url);
        $key = ltrim($parsed['path'], '/'); // Quita el slash inicial

        // 3. Eliminar el objeto de S3
        try {
            $s3->deleteObject([
                'Bucket' => $bucket,
                'Key' => $key
            ]);
        } catch (AwsException $e) {
            // No es fatal, se continúa con la eliminación en BD
            error_log("No se pudo eliminar de S3: " . $e->getAwsErrorMessage());
        }

        // 4. Eliminar el registro de la base de datos
        $stmt = $conn->prepare("DELETE FROM periodos_imagenes WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("Error al eliminar imagen ID $id: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Solicitud inválida']);
}
