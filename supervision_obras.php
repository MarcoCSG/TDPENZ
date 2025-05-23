<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['municipio_id'])) {
    header("Location: index.php");
    exit;
}

$municipio_id = $_SESSION['municipio_id'];

// Obtener las obras del municipio
$stmt = $conn->prepare("SELECT id, nombre FROM obras WHERE municipio_id = ?");
$stmt->execute([$municipio_id]);
$obras = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Supervisión de Obras</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="form-container">
        <h2>Selecciona una obra</h2>

        <form action="ver_supervision.php" method="POST">
            <label for="obra_id">Obra registrada en este municipio:</label>
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
