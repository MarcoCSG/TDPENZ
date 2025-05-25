<?php
session_start();
include 'includes/db.php';

// Solo administradores pueden acceder
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'admin') {
    header("Location: index.php");
    exit;
}

// Obtener municipios
$stmt = $conn->prepare("SELECT id, nombre FROM municipios ORDER BY nombre");
$stmt->execute();
$municipios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registrar Usuario</title>
    <!-- <link rel="stylesheet" href="assets/css/style.css"> -->
        <link rel="stylesheet" href="assets/css/registrar_usuarios.css">
</head>
<body>
    <div class="menu-container">
        <img src="assets/img/logo.png" class="logo" alt="Logo">
        <h1>Registrar Nuevo Usuario</h1>

        <form action="guardar_usuario.php" method="POST">
            <input type="text" name="nombre" placeholder="Nombre completo" required>
            <input type="email" name="correo" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>

            <label for="tipo_usuario_id">Tipo de usuario:</label>
            <select name="tipo_usuario_id" id="tipo_usuario_id" required>
                <option value="admin">Administrador</option>
                <option value="supervisor">Supervisor</option>
            </select>

            <label>Municipios asignados:</label>
            <div class="municipios-list">
                <?php foreach ($municipios as $m): ?>
                    <label>
                        <input type="checkbox" name="municipios[]" value="<?= $m['id'] ?>">
                        <?= htmlspecialchars($m['nombre']) ?>
                    </label><br>
                <?php endforeach; ?>
            </div>

            <button type="submit">Registrar</button>
        </form>
    </div>
</body>
</html>
