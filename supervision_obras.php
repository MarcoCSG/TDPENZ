<?php
session_start();
include 'includes/db.php';

// Verificar que el usuario, municipio y año estén en sesión
if (!isset($_SESSION['usuario_id'], $_SESSION['municipio_id'], $_SESSION['anio'])) {
    header("Location: index.php");
    exit;
}

$municipio_id = $_SESSION['municipio_id'];
$anio = $_SESSION['anio'];

// Obtener las obras filtradas por municipio y año
$stmt = $conn->prepare("SELECT id, nombre FROM obras WHERE municipio_id = ? AND anio = ?");
$stmt->execute([$municipio_id, $anio]);
$obras = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="icon" href="assets/img/logo_redondo.png" type="image/x-icon">
    <title>Supervisión de Obras</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="form-container">
        <h2>Selecciona una obra</h2>

        <form action="dashboard_obra.php" method="POST">
            <label for="obra_id">Obras registradas en este municipio en <?= htmlspecialchars($anio) ?>:</label>
            <select name="obra_id" id="obra_id" required>
                <option value="">-- Selecciona una obra --</option>
                <?php foreach ($obras as $obra): ?>
                    <option value="<?= $obra['id'] ?>">
                        <?= htmlspecialchars($obra['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Ver supervisión</button>
        </form>
    </div>
</body>
</html>
