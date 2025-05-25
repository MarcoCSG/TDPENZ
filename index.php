<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="icon" href="assets/img/logo_redondo.png" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login">
    <div class="login-box">
        <img src="assets/img/logo.png" class="logo">
        <img src="assets/img/acceso.png" class="avatar">

        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">Usuario o contraseña incorrectos.</div>
        <?php endif; ?>

        <form action="includes/auth.php" method="POST">
            <input type="email" name="correo" placeholder="Correo" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Iniciar sesión</button>
        </form>
    </div>
</body>
</html>
