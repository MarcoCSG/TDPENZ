<?php
require_once __DIR__ . '/../includes/db.php';
session_start();

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['error'] = "No has iniciado sesión.";
    header("Location: ../index.php");
    exit;
}

// Validar datos del formulario
if (!isset($_POST['obra_id'], $_POST['estimacion_id'], $_POST['fecha_revision_campo'], $_POST['fecha_revision_gabinete'], $_POST['estatus'])) {
    $_SESSION['error'] = "Faltan datos obligatorios.";
    header("Location: ../dashboard_obra.php");
    exit;
}

$obra_id = (int) $_POST['obra_id'];
$estimacion_id = (int) $_POST['estimacion_id'];
$fecha_revision_campo = $_POST['fecha_revision_campo'];
$fecha_revision_gabinete = $_POST['fecha_revision_gabinete'];
$estatus = $_POST['estatus'];
$observaciones = $_POST['observaciones'] ?? null;
$usuario_id = $_SESSION['usuario_id'];

// Verificar si ya existe un dictamen para esa estimación
$stmt = $conn->prepare("SELECT id FROM dictamenes_procedencia WHERE obra_id = ? AND estimacion_id = ?");
$stmt->execute([$obra_id, $estimacion_id]);
$existe = $stmt->fetchColumn();

if ($existe) {
    // Actualizar
    $stmt = $conn->prepare("UPDATE dictamenes_procedencia 
                            SET fecha_revision_campo = ?, fecha_revision_gabinete = ?, estatus = ?, observaciones = ?, actualizado_en = NOW()
                            WHERE obra_id = ? AND estimacion_id = ?");
    $stmt->execute([$fecha_revision_campo, $fecha_revision_gabinete, $estatus, $observaciones, $obra_id, $estimacion_id]);
    $_SESSION['success'] = "Dictamen actualizado correctamente.";
} else {
    // Insertar
    $stmt = $conn->prepare("INSERT INTO dictamenes_procedencia 
        (obra_id, estimacion_id, fecha_revision_campo, fecha_revision_gabinete, estatus, observaciones, creado_por)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$obra_id, $estimacion_id, $fecha_revision_campo, $fecha_revision_gabinete, $estatus, $observaciones, $usuario_id]);
    $_SESSION['success'] = "Dictamen guardado correctamente.";
}

// Redirigir a la vista
    header("Location: ../dashboard_obra.php?seccion=dictamen&id=$obra_id&estimacion_id=$estimacion_id");
exit;
?>
