<?php
session_start();
require '../includes/db.php';

header('Content-Type: application/json');

// Verificar método POST y archivos
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
$response = ['success' => false, 'error' => ''];

try {
    // Verificar errores en la subida
    if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
        $response['error'] = 'Error al subir el archivo: Código ' . $_FILES['imagen']['error'];
        echo json_encode($response);
        exit;
    }

    // Obtener imagen existente
    $stmt = $conn->prepare("SELECT id, ruta FROM estimacion_imagenes WHERE id = ?");
    $stmt->execute([$id]);
    $imagenExistente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$imagenExistente) {
        $response['error'] = 'La imagen a reemplazar no existe';
        echo json_encode($response);
        exit;
    }

    // Configurar rutas absolutas
    $baseDir = $_SERVER['DOCUMENT_ROOT'] . '';
    $uploadDir = $baseDir . 'uploads/estimaciones/';
    $rutaPublica = 'uploads/estimaciones/';

    // Crear directorio si no existe
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            $response['error'] = 'No se pudo crear el directorio de uploads';
            echo json_encode($response);
            exit;
        }
    }

    // Generar nombre único para el nuevo archivo
    $extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
    $nombreArchivo = uniqid() . '.' . $extension;
    $rutaCompleta = $uploadDir . $nombreArchivo;
    $rutaPublicaNueva = $rutaPublica . $nombreArchivo;

    // Mover el nuevo archivo
    if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaCompleta)) {
        $response['error'] = 'Error al mover el archivo subido';
        echo json_encode($response);
        exit;
    }

    // Eliminar el archivo antiguo si existe
    if ($imagenExistente['ruta'] && file_exists($baseDir . $imagenExistente['ruta'])) {
        if (!unlink($baseDir . $imagenExistente['ruta'])) {
            $response['warning'] = 'No se pudo eliminar el archivo antiguo';
        }
    }

    // Actualizar la base de datos con la ruta pública
    $stmt = $conn->prepare("UPDATE estimacion_imagenes SET ruta = ? WHERE id = ?");
    if ($stmt->execute([$rutaPublicaNueva, $id])) {
        $response['success'] = true;
        $response['nuevaRuta'] = $rutaPublicaNueva;
    } else {
        $response['error'] = 'Error al actualizar la base de datos';
        // Intentar eliminar el nuevo archivo si falló la BD
        if (file_exists($rutaCompleta)) {
            unlink($rutaCompleta);
        }
    }
} catch (PDOException $e) {
    $response['error'] = 'Error de base de datos: ' . $e->getMessage();
} catch (Exception $e) {
    $response['error'] = 'Error general: ' . $e->getMessage();
}

echo json_encode($response);
?>