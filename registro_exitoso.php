<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registro Exitoso</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }

        .success-container {
            max-width: 500px;
            margin: 100px auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .success-container img {
            width: 100px;
            margin-bottom: 20px;
        }

        .success-container h1 {
            color: #28a745;
            margin-bottom: 10px;
        }

        .success-container p {
            color: #333;
            margin-bottom: 30px;
        }

        .success-container a {
            text-decoration: none;
            background: #007bff;
            color: white;
            padding: 12px 20px;
            border-radius: 6px;
            transition: background 0.3s;
        }

        .success-container a:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>

<div class="success-container">
    <img src="assets/img/logo.png" alt="Logo">
    <h1>¡Usuario registrado con éxito!</h1>
    <p>El nuevo usuario ha sido agregado correctamente al sistema.</p>
    <a href="menu.php">Volver al menú principal</a>
</div>

</body>
</html>
