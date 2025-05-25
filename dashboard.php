<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Procesar formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['municipio_id'] = $_POST['municipio_id'];
    $_SESSION['anio'] = $_POST['anio'];
    header("Location: menu.php"); // Redirige con GET
    exit;
}

// Obtener municipios asignados al usuario
$usuario_id = $_SESSION['usuario_id'];
$query = $conn->prepare("
    SELECT m.id, m.nombre 
    FROM municipios m
    JOIN usuario_municipio um ON um.municipio_id = m.id
    WHERE um.usuario_id = ?
");
$query->execute([$usuario_id]);
$municipios = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Seleccionar municipio</title>
        <link rel="icon" href="assets/img/logo_redondo.png" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <img src="assets/img/logo.png" class="logo" alt="Logo empresa">
        <h2>Selecciona un municipio y año</h2>

        <form action="dashboard.php" method="POST">
            <label for="municipio">Municipio:</label>
            <select name="municipio_id" id="municipio" required>
                <?php foreach ($municipios as $m): ?>
                    <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="anio">Año:</label>
            <select name="anio" id="anio" required>
                <?php for ($a = 2022; $a <= 2025; $a++): ?>
                    <option value="<?= $a ?>"><?= $a ?></option>
                <?php endfor; ?>
            </select>

            <button type="submit">Continuar</button>
        </form>
    </div>
</body>
</html>
