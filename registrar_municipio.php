<?php
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $municipio = htmlspecialchars(trim($_POST['municipio']));

    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        $logo = $_FILES['logo'];
        $ext = pathinfo($logo['name'], PATHINFO_EXTENSION);
        $filename = strtolower(preg_replace("/[^a-zA-Z0-9]/", "_", $municipio)) . "_logo." . $ext;
        $uploadDir = 'uploads_empresas/';
        $uploadPath = $uploadDir . $filename;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (move_uploaded_file($logo['tmp_name'], $uploadPath)) {
            // Guardar en base de datos
            $stmt = $conn->prepare("INSERT INTO municipios (nombre, logo_ruta) VALUES (?, ?)");
            $stmt->execute([$municipio, $uploadPath]);

            echo "<p>Municipio <strong>'$municipio'</strong> registrado con éxito.</p>";
            echo "<img src='$uploadPath' alt='Logo' style='max-width:150px;'>";
        } else {
            echo "Error al mover el archivo.";
        }
    } else {
        echo "Error al subir el logo.";
    }
} else {
    echo "Acceso no permitido.";
}
?>
