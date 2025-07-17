<?php
session_start();
include 'includes/db.php';

// Verificar si usuario está logueado y es administrador
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Obtener el tipo de usuario desde la base de datos
$stmt = $conn->prepare("SELECT tipo_usuario_id FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$tipo_usuario_id = $stmt->fetchColumn();

// Solo permitir acceso si es administrador (tipo_usuario_id = 1)
if ($tipo_usuario_id != 1) {
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
    <link rel="icon" href="assets/img/logo_redondo.png" type="image/x-icon">

    <meta charset="UTF-8">
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
                <option value="1">Administrador</option>
                <option value="2">Supervisor</option>

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