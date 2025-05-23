<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'admin') {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'] ?? null;
if ($id) {
    $stmt = $conn->prepare("DELETE FROM obras WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: obras.php?eliminado=ok");
exit;
