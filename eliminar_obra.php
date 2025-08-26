<?php
session_start();
include 'includes/db.php';

// Verificar si usuario está logueado y tiene las variables de sesión necesarias
if (!isset($_SESSION['usuario_id'], $_SESSION['municipio_id'], $_SESSION['anio'])) {
    header("Location: index.php");
    exit;
}

// Verificar que sea administrador
$stmt = $conn->prepare("SELECT tipo_usuario_id FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$tipo_usuario_id = $stmt->fetchColumn();

if ($tipo_usuario_id != 1) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'] ?? null;
if ($id) {
    $stmt = $conn->prepare("DELETE FROM obras WHERE id = ?");
    $stmt->execute([$id]);
    
    header("Location: registrar_obra.php?eliminado=ok");
    exit;
}
