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

// Validar campos requeridos
$required_fields = ['obra_id', 'estimacion_id', 'estatus_estimacion', 'observaciones'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $_SESSION['error'] = "Faltan datos obligatorios: $field";
        header("Location: ../dashboard_obra.php?seccion=cedula&id=" . ($_POST['obra_id'] ?? ''));
        exit;
    }
}

// Sanitizar y validar datos
$obra_id = filter_input(INPUT_POST, 'obra_id', FILTER_VALIDATE_INT);
$estimacion_id = filter_input(INPUT_POST, 'estimacion_id', FILTER_VALIDATE_INT);
$estatus = in_array($_POST['estatus_estimacion'], ['aprobada', 'no_aprobada']) ? $_POST['estatus_estimacion'] : null;
$observaciones = trim($_POST['observaciones']);
$usuario_id = $_SESSION['usuario_id'];

if (!$obra_id || !$estimacion_id || !$estatus) {
    $_SESSION['error'] = "Datos no válidos";
    header("Location: ../dashboard_obra.php?seccion=cedula&id=$obra_id");
    exit;
}

// Verificar que exista la obra y estimación
try {
    $stmt = $conn->prepare("SELECT 1 FROM obras WHERE id = ?");
    $stmt->execute([$obra_id]);
    if (!$stmt->fetch()) {
        throw new Exception("La obra no existe");
    }

    $stmt = $conn->prepare("SELECT 1 FROM estimaciones WHERE id = ? AND obra_id = ?");
    $stmt->execute([$estimacion_id, $obra_id]);
    if (!$stmt->fetch()) {
        throw new Exception("La estimación no existe para esta obra");
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: ../dashboard_obra.php?seccion=cedula&id=$obra_id");
    exit;
}

// Procesar checkboxes
$checks = [
    'caratula_estimacion' => isset($_POST['check_caratula_estimacion']) ? 1 : 0,
    'resumen_partidas' => isset($_POST['check_resumen_partidas']) ? 1 : 0,
    'estado_cuentas' => isset($_POST['check_estado_cuentas']) ? 1 : 0,
    'estimacion_check' => isset($_POST['check_estimacion']) ? 1 : 0,
    'volumenes_obra' => isset($_POST['check_volumenes_obra']) ? 1 : 0,
    'croquis_volumenes' => isset($_POST['check_croquis_volumenes']) ? 1 : 0,
    'reporte_fotografico' => isset($_POST['check_reporte_fotografico']) ? 1 : 0,
    'pruebas_laboratorios' => isset($_POST['check_pruebas_laboratorios']) ? 1 : 0
];

// Valores numéricos
$importe_retenciones = isset($_POST['importe_retenciones']) ? (float)$_POST['importe_retenciones'] : 0;
$liquido_pagar = isset($_POST['liquido_pagar']) ? (float)$_POST['liquido_pagar'] : 0;

try {
    $conn->beginTransaction();

    // Verificar si ya existe
    $stmt = $conn->prepare("SELECT id FROM cedulas_estatus WHERE obra_id = ? AND estimacion_id = ?");
    $stmt->execute([$obra_id, $estimacion_id]);
    $cedula_existente = $stmt->fetch();

    if ($cedula_existente) {
        // Actualizar registro existente
        $stmt = $conn->prepare("UPDATE cedulas_estatus SET 
            estatus = ?,
            observaciones = ?,
            caratula_estimacion = ?,
            resumen_partidas = ?,
            estado_cuentas = ?,
            estimacion_check = ?,
            volumenes_obra = ?,
            croquis_volumenes = ?,
            reporte_fotografico = ?,
            pruebas_laboratorios = ?,
            importe_retenciones = ?,
            liquido_pagar = ?,
            creado_por = ?,
            actualizado_en = NOW()
            WHERE id = ?");
        
        $params = [
            $estatus,
            $observaciones,
            $checks['caratula_estimacion'],
            $checks['resumen_partidas'],
            $checks['estado_cuentas'],
            $checks['estimacion_check'],
            $checks['volumenes_obra'],
            $checks['croquis_volumenes'],
            $checks['reporte_fotografico'],
            $checks['pruebas_laboratorios'],
            $importe_retenciones,
            $liquido_pagar,
            $usuario_id,
            $cedula_existente['id']
        ];
    } else {
        // Insertar nuevo registro
        $stmt = $conn->prepare("INSERT INTO cedulas_estatus (
            obra_id, estimacion_id, estatus, observaciones,
            caratula_estimacion, resumen_partidas, estado_cuentas,
            estimacion_check, volumenes_obra, croquis_volumenes,
            reporte_fotografico, pruebas_laboratorios,
            importe_retenciones, liquido_pagar, creado_por
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $params = [
            $obra_id,
            $estimacion_id,
            $estatus,
            $observaciones,
            $checks['caratula_estimacion'],
            $checks['resumen_partidas'],
            $checks['estado_cuentas'],
            $checks['estimacion_check'],
            $checks['volumenes_obra'],
            $checks['croquis_volumenes'],
            $checks['reporte_fotografico'],
            $checks['pruebas_laboratorios'],
            $importe_retenciones,
            $liquido_pagar,
            $usuario_id
        ];
    }

    $stmt->execute($params);
    $conn->commit();

    $_SESSION['success'] = "Cédula guardada correctamente";
    header("Location: ../dashboard_obra.php?seccion=minuta&id=$obra_id&estimacion_id=$estimacion_id");
    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    $_SESSION['error'] = "Error al guardar: " . $e->getMessage();
    header("Location: ../dashboard_obra.php?seccion=cedula&id=$obra_id");
    exit;
}