<?php
// Habilitar errores para desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Manejo seguro de sesiones
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexión a la base de datos
require_once __DIR__ . '/../includes/db.php';

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['error'] = "Debe iniciar sesión para acceder a esta función";
    header("Location: ../login.php");
    exit;
}

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Método de solicitud no permitido";
    header("Location: ../dashboard_obra.php");
    exit;
}

// Validar campos obligatorios
$required = ['obra_id', 'estimacion_id', 'fecha_calculo'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        $_SESSION['error'] = "El campo $field es requerido";
        header("Location: ../dashboard_obra.php?seccion=minuta&id=" . ($_POST['obra_id'] ?? ''));
        exit;
    }
}

// Sanitizar entradas
$obra_id = (int)$_POST['obra_id'];
$estimacion_id = (int)$_POST['estimacion_id'];

// Verificar existencia de obra y estimación
try {
    $stmt = $conn->prepare("SELECT id FROM obras WHERE id = ?");
    $stmt->execute([$obra_id]);
    if (!$stmt->fetch()) {
        throw new Exception("La obra especificada no existe");
    }

    $stmt = $conn->prepare("SELECT id FROM estimaciones WHERE id = ? AND obra_id = ?");
    $stmt->execute([$estimacion_id, $obra_id]);
    if (!$stmt->fetch()) {
        throw new Exception("La estimación especificada no existe para esta obra");
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: ../dashboard_obra.php?seccion=minuta&id=" . $obra_id);
    exit;
}

// Preparar datos para inserción
$datos = [
    'obra_id' => $obra_id,
    'estimacion_id' => $estimacion_id,
    'avance_fisico' => $_POST['avance_fisico'] ?? null,
    'avance_financiero' => $_POST['avance_financiero'] ?? null,
    'conceptos_contrato' => isset($_POST['conceptos_contrato']) ? (int)$_POST['conceptos_contrato'] : null,
    'conceptos_ejecutados' => isset($_POST['conceptos_ejecutados']) ? (int)$_POST['conceptos_ejecutados'] : null,
    'partidas_ejecutadas' => $_POST['partidas_ejecutadas'] ?? null,
    'conceptos_por_ejecutar' => isset($_POST['conceptos_por_ejecutar']) ? (int)$_POST['conceptos_por_ejecutar'] : null,
    'partidas_por_ejecutar' => $_POST['partidas_por_ejecutar'] ?? null,
    'dias_transcurridos' => isset($_POST['dias_transcurridos']) ? (int)$_POST['dias_transcurridos'] : null,
    'conceptos_extraordinarios' => isset($_POST['conceptos_extraordinarios']) ? (int)$_POST['conceptos_extraordinarios'] : 0,
    'dias_ampliacion' => isset($_POST['dias_ampliacion']) ? (int)$_POST['dias_ampliacion'] : 0
];

// Insertar en la base de datos
try {
    $conn->beginTransaction();

    $sql = "INSERT INTO minutas_avance (
        obra_id, estimacion_id, avance_fisico, avance_financiero,
        conceptos_contrato, conceptos_ejecutados, partidas_ejecutadas,
        conceptos_por_ejecutar, partidas_por_ejecutar, dias_transcurridos,
        conceptos_extraordinarios, dias_ampliacion
    ) VALUES (
        :obra_id, :estimacion_id, :avance_fisico, :avance_financiero,
        :conceptos_contrato, :conceptos_ejecutados, :partidas_ejecutadas,
        :conceptos_por_ejecutar, :partidas_por_ejecutar, :dias_transcurridos,
        :conceptos_extraordinarios, :dias_ampliacion
    )";

    $stmt = $conn->prepare($sql);
    $stmt->execute($datos);

    $conn->commit();
    
    $_SESSION['success'] = "Minuta de avance guardada correctamente";
    header("Location: ../dashboard_obra.php?seccion=minuta&id=$obra_id&estimacion_id=$estimacion_id");
    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    $_SESSION['error'] = "Error al guardar la minuta: " . $e->getMessage();
    header("Location: ../dashboard_obra.php?seccion=minuta&id=$obra_id");
    exit;
}
