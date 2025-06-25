<?php
session_start();
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    
    try {
        // 1. Obtener la ruta de la imagen
        $stmt = $conn->prepare("SELECT ruta FROM periodos_imagenes WHERE id = ?");
        $stmt->execute([$id]);
        $imagen = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($imagen && !empty($imagen['ruta'])) {
            // 2. Construir la ruta correcta (3 métodos alternativos)
            
            // Método 1: Usando DOCUMENT_ROOT (recomendado)
            $ruta_completa = $_SERVER['DOCUMENT_ROOT'] . '/' . $imagen['ruta'];
            
            // Método 2: Ruta relativa al script actual (alternativa)
            // $ruta_completa = dirname(__DIR__) . '/' . $imagen['ruta'];
            
            // Método 3: Para desarrollo local (XAMPP/WAMP)
            // $ruta_completa = 'C:/xampp/htdocs/' . $imagen['ruta']; // Ajusta según tu instalación
            
            // Normalizar las barras de directorio (para Windows)
            $ruta_completa = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $ruta_completa);
            
            // 3. Verificación detallada antes de eliminar
            if (file_exists($ruta_completa)) {
                if (is_writable($ruta_completa)) {
                    if (!unlink($ruta_completa)) {
                        throw new Exception("Error al eliminar el archivo (permisos?)");
                    }
                } else {
                    throw new Exception("El archivo existe pero no tiene permisos de escritura");
                }
            } else {
                // Registrar la ruta que no se encontró para depuración
                error_log("Archivo no encontrado: " . $ruta_completa);
                
                // Opcional: Verificar si existe en otras ubicaciones comunes
                $ubicaciones_alternativas = [
                    $_SERVER['DOCUMENT_ROOT'] . '/../' . $imagen['ruta'],
                    dirname(__DIR__, 2) . '/' . $imagen['ruta'],
                    'C:/xampp/htdocs/' . $imagen['ruta']
                ];
                
                foreach ($ubicaciones_alternativas as $ubicacion) {
                    $ubicacion = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $ubicacion);
                    if (file_exists($ubicacion)) {
                        error_log("El archivo existe en ubicación alternativa: " . $ubicacion);
                        break;
                    }
                }
            }
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
?>