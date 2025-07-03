<?php
session_start();
require_once('../includes/db.php');

// Verificar autenticación y permisos
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}

// Validar método de envío
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Método de solicitud no permitido";
    header("Location: dashboard_obra.php?seccion=cedula&id=" . ($_POST['obra_id'] ?? ''));
    exit;
}

// Validar datos obligatorios
$required_fields = ['obra_id', 'estimacion_id', 'estatus_estimacion', 'observaciones'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field])) {
        $_SESSION['error'] = "Faltan datos obligatorios";
        header("Location: dashboard_obra.php?seccion=cedula&id=" . ($_POST['obra_id'] ?? ''));
        exit;
    }
}

// Sanitizar y validar datos
$obra_id = filter_input(INPUT_POST, 'obra_id', FILTER_VALIDATE_INT);
$estimacion_id = filter_input(INPUT_POST, 'estimacion_id', FILTER_VALIDATE_INT);
$estatus = $_POST['estatus_estimacion'];
$observaciones = trim($_POST['observaciones']); 

// Validar valores
if (!$obra_id || !$estimacion_id || !in_array($estatus, ['aprobada', 'no_aprobada']) || empty($observaciones)) {
    $_SESSION['error'] = "Datos no válidos";
    header("Location: dashboard_obra.php?seccion=cedula&id=" . $obra_id);
    exit;
}

// Recoger valores de los checkboxes (1 si marcado, 0 si no)
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

try {
    $conn->beginTransaction();

    // Verificar si ya existe una cédula y obtener su ID
    $stmtCheck = $conn->prepare("SELECT id FROM cedulas_estatus WHERE obra_id = ? AND estimacion_id = ?");
    $stmtCheck->execute([$obra_id, $estimacion_id]);
    $cedulaExistente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($cedulaExistente) {
        // Actualizar usando el ID único
        $stmt = $conn->prepare("UPDATE cedulas_estatus SET 
            estatus = :estatus,
            observaciones = :observaciones,
            caratula_estimacion = :caratula_estimacion,
            resumen_partidas = :resumen_partidas,
            estado_cuentas = :estado_cuentas,
            estimacion_check = :estimacion_check,
            volumenes_obra = :volumenes_obra,
            croquis_volumenes = :croquis_volumenes,
            reporte_fotografico = :reporte_fotografico,
            pruebas_laboratorios = :pruebas_laboratorios,
            creado_por = :creado_por,
            actualizado_en = NOW()
            WHERE id = :id
        ");

        $params[':id'] = $cedulaExistente['id'];

    } else {
        // Insertar nuevo registro
        $stmt = $conn->prepare("INSERT INTO cedulas_estatus (
            obra_id, estimacion_id, estatus, observaciones, 
            caratula_estimacion, resumen_partidas, estado_cuentas,
            estimacion_check, volumenes_obra, croquis_volumenes, 
            reporte_fotografico, pruebas_laboratorios, creado_por
        ) VALUES (
            :obra_id, :estimacion_id, :estatus, :observaciones,
            :caratula_estimacion, :resumen_partidas, :estado_cuentas,
            :estimacion_check, :volumenes_obra, :croquis_volumenes,
            :reporte_fotografico, :pruebas_laboratorios, :creado_por
        )");
    }

    // Parámetros comunes
    $params = [
        ':obra_id' => $obra_id,
        ':estimacion_id' => $estimacion_id,
        ':estatus' => $estatus,
        ':observaciones' => $observaciones,
        ':caratula_estimacion' => $checks['caratula_estimacion'],
        ':resumen_partidas' => $checks['resumen_partidas'],
        ':estado_cuentas' => $checks['estado_cuentas'],
        ':estimacion_check' => $checks['estimacion_check'],
        ':volumenes_obra' => $checks['volumenes_obra'],
        ':croquis_volumenes' => $checks['croquis_volumenes'],
        ':reporte_fotografico' => $checks['reporte_fotografico'],
        ':pruebas_laboratorios' => $checks['pruebas_laboratorios'],
        ':creado_por' => $_SESSION['usuario_id']
    ];

// Agregar ID solo si es una actualización
if ($cedulaExistente) {
    $params[':id'] = $cedulaExistente['id'];
}

// Ejecutar la consulta
$stmt->execute($params);


    $conn->commit();

    $_SESSION['success'] = "Cédula guardada correctamente.";
header("Location: ../dashboard_obra.php?seccion=cedula&id=$obra_id");

    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    $_SESSION['error'] = "Error al guardar la cédula: " . $e->getMessage();
header("Location: ../dashboard_obra.php?seccion=cedula&id=$obra_id");

    exit;
} catch (PDOException $e) {
    // Revertir transacción en caso de error
    $conn->rollBack();
    
    $_SESSION['error'] = "Error al guardar la cédula: " . $e->getMessage();
header("Location: ../dashboard_obra.php?seccion=cedula&id=$obra_id");

    exit;
}