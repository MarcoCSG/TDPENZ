<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || !isset($_POST['municipio_id']) || !isset($_POST['anio'])) {
    header("Location: index.php");
    exit;
}

$_SESSION['municipio_id'] = $_POST['municipio_id'];
$_SESSION['anio'] = $_POST['anio'];

// Obtener nombre del municipio seleccionado
$stmt = $conn->prepare("SELECT nombre FROM municipios WHERE id = ?");
$stmt->execute([$_SESSION['municipio_id']]);
$municipio = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Menú principal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="menu-container">
        <img src="assets/img/logo.png" class="logo" alt="Logo empresa">
        <h1><?= htmlspecialchars($municipio) ?></h1>
        <h2>PERIODO: <?= htmlspecialchars($_SESSION['anio']) ?></h2>

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
    </div>

</body>

</html>