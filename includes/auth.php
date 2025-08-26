<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['correo'];
    $password = $_POST['password'];

    // Traer también el tipo de usuario
    $stmt = $conn->prepare("SELECT id, password, tipo_usuario_id FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($password, $usuario['password'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['tipo_usuario_id'] = $usuario['tipo_usuario_id']; // 🔹 Guardamos el tipo en sesión
        header("Location: ../dashboard.php");
        exit;
    } else {
        header("Location: ../index.php?error=1");
        exit;
    }
}
