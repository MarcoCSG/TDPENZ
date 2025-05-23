<?php
include 'db.php';

$nombre = "Admin Test";
$correo = "admin@example.com";
$password = password_hash("123456", PASSWORD_DEFAULT);
$tipo_usuario_id = 1; // administrador

$stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo, password, tipo_usuario_id) VALUES (?, ?, ?, ?)");
$stmt->execute([$nombre, $correo, $password, $tipo_usuario_id]);

echo "Usuario creado correctamente";
