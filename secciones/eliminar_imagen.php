<?php
session_start();
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    
    try {
        // Primero obtenemos la ruta para eliminar el archivo físico
        $stmt = $conn->prepare("SELECT ruta FROM estimacion_imagenes WHERE id = ?");
        $stmt->execute([$id]);
        $imagen = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($imagen && file_exists($imagen['ruta'])) {
            unlink($imagen['ruta']);
        }
        
        // Eliminamos de la base de datos
        $stmt = $conn->prepare("DELETE FROM estimacion_imagenes WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Solicitud inválida']);
}
?>