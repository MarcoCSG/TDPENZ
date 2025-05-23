<?php
session_start();
include 'db.php';

$correo = $_POST['correo'];
$password = $_POST['password'];

$query = $conn->prepare("SELECT * FROM usuarios WHERE correo = ?");
$query->execute([$correo]);
$usuario = $query->fetch(PDO::FETCH_ASSOC);

if ($usuario && password_verify($password, $usuario['password'])) {
    $_SESSION['usuario_id'] = $usuario['id'];
    header("Location: dashboard.php");
} else {
    echo "Usuario o contraseña incorrectos";
}
