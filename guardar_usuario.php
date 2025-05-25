<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'admin') {
    header("Location: index.php");
    exit;
}

// Validar datos
$nombre = $_POST['nombre'] ?? '';
$correo = $_POST['correo'] ?? '';
$password = $_POST['password'] ?? '';
$tipo_usuario_id = $_POST['tipo_usuario_id'] ?? '';
$municipios = $_POST['municipios'] ?? [];

if (!$nombre || !$correo || !$password || !$tipo_usuario_id || empty($municipios)) {
    die("Faltan datos");
}

// Verificar si el correo ya existe
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = ?");
$stmt->execute([$correo]);
if ($stmt->fetch()) {
    die("El correo ya está registrado.");
}

// Insertar usuario
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo, password, tipo_usuario_id) VALUES (?, ?, ?, ?)");
$stmt->execute([$nombre, $correo, $hashed_password, $rol]);
$usuario_id = $conn->lastInsertId();

// Asignar municipios
$stmt = $conn->prepare("INSERT INTO usuario_municipio (usuario_id, municipio_id) VALUES (?, ?)");
foreach ($municipios as $m_id) {
    $stmt->execute([$usuario_id, $m_id]);
}

header("Location: registro_exitoso.php");
exit;

