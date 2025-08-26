<?php
// Configuración de sesión más robusta
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 si usas HTTPS

session_start();
include 'includes/db.php';

// Debug: Verificar variables de sesión
error_log("Debug Menu - usuario_id: " . ($_SESSION['usuario_id'] ?? 'NO SET'));
error_log("Debug Menu - municipio_id: " . ($_SESSION['municipio_id'] ?? 'NO SET'));
error_log("Debug Menu - anio: " . ($_SESSION['anio'] ?? 'NO SET'));

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['municipio_id']) || !isset($_SESSION['anio'])) {
    error_log("Debug Menu - Redirigiendo a index.php - Variables de sesión faltantes");
    header("Location: index.php");
    exit;
}

// Solo usar variables de sesión
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

            <?php if (isset($_SESSION['tipo_usuario_id']) && $_SESSION['tipo_usuario_id'] == 1): ?>
                <a href="registrar_usuario.php" class="menu-button">
                    <img src="assets/img/agregar-user.png" alt="registro">
                    Registrar Usuario
                </a>
            <?php endif; ?>

            <?php if (isset($_SESSION['tipo_usuario_id']) && $_SESSION['tipo_usuario_id'] == 1): ?>
                <a href="registrar_usuario.php" class="menu-button">
                    <img src="assets/img/actualizar.png" alt="registro">
                    Cambiar de municipo y año
                </a>
            <?php endif; ?>


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