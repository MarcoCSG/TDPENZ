<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['municipio_id']) || !isset($_SESSION['anio'])) {
    header("Location: index.php");
    exit;
}

// Solo usar variables de sesión, nunca POST
$municipio_id = $_SESSION['municipio_id'];
$anio = $_SESSION['anio'];

// Obtener nombre del municipio seleccionado
$stmt = $conn->prepare("SELECT nombre FROM municipios WHERE id = ?");
$stmt->execute([$municipio_id]);
$municipio = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Menú principal</title>
    <link rel="icon" href="assets/img/logo_redondo.png" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="menu-container">
        <img src="assets/img/logo.png" class="logo" alt="Logo empresa">
        <h1><?= htmlspecialchars($municipio) ?></h1>
        <h2>PERIODO: <?= htmlspecialchars($anio) ?></h2>

        <div class="menu-buttons">
            <a href="supervision_obras.php" class="menu-button">
                <img src="assets/img/supervisor.png" alt="supervision">
                Supervisión de obras
            </a>

            <a href="#" class="menu-button">
                <img src="assets/img/auditor.png" alt="auditoria">
                Auditoría técnica
            </a>
            <a href="registrar_usuario.php" class="menu-button">
                <img src="assets/img/agregar-user.png" alt="registro">
                Registrar Usuario
            </a>
            <a href="registrar_obra.php" class="menu-button">
                <img src="assets/img/configuraciones.png" alt="obras">
                Altas de obras
            </a>
        </div>
        <div style="text-align: center; margin-top: 20px;">
            <a href="logout.php" class="menu-button" style="background-color: #d9534f; color: white;">
                <img src="assets/img/cerrar-sesion.png" alt="Cerrar sesión" style="width: 30px; height: 30px; vertical-align: middle;">
                Cerrar sesión
            </a>
        </div>

    </div>
</body>

</html>