<?php
session_start();
include_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['usuario_id'], $_SESSION['municipio_id'], $_SESSION['anio'])) {
    header("Location: index.php");
    exit;
}

$municipio_id = $_SESSION['municipio_id'];
$anio = $_SESSION['anio'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $fuente = $_POST['fuente'];
    $localidad = $_POST['localidad'];
    $lat = isset($_POST['latitud']) ? floatval($_POST['latitud']) : null;
    $lng = isset($_POST['longitud']) ? floatval($_POST['longitud']) : null;

    // Contratación
    $contratista = $_POST['contratista'] ?? null;
    $numero_contrato = $_POST['numero_contrato'] ?? null;
    $porcentaje_anticipo = $_POST['porcentaje_anticipo'] ?? null;
    $monto_contratado = $_POST['monto_contratado'] ?? null;
    $tipo_adjudicacion = $_POST['tipo_adjudicacion'] ?? null;
    $anticipo = $_POST['anticipo'] ?? null;
    $fecha_firma = $_POST['fecha_firma'] ?? null;
    $fecha_inicio_contrato = $_POST['fecha_inicio_contrato'] ?? null;
    $fecha_cierre = $_POST['fecha_cierre'] ?? null;

    // Convenios
    $ampliacion_monto = $_POST['ampliacion_monto'] ?? null;
    $reduccion_monto = $_POST['reduccion_monto'] ?? null;
    $ampliacion_plazo = $_POST['ampliacion_plazo'] ?? null;
    $reduccion_plazo = $_POST['reduccion_plazo'] ?? null;
    $diferimiento_periodo = $_POST['diferimiento_periodo'] ?? null;

    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        die("Error: Coordenadas fuera de rango.");
    }

    // Insertar obra con todos los campos
    $stmt = $conn->prepare("INSERT INTO obras (
        nombre, descripcion, fuente_financiamiento, localidad, latitud, longitud, municipio_id, anio,
        contratista, numero_contrato, porcentaje_anticipo, monto_contratado, tipo_adjudicacion, anticipo, fecha_firma, fecha_inicio_contrato, fecha_cierre,
        ampliacion_monto, reduccion_monto, ampliacion_plazo, reduccion_plazo, diferimiento_periodo
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $nombre,
        $descripcion,
        $fuente,
        $localidad,
        $lat,
        $lng,
        $municipio_id,
        $anio,
        $contratista,
        $numero_contrato,
        $porcentaje_anticipo,
        $monto_contratado,
        $tipo_adjudicacion,
        $anticipo,
        $fecha_firma,
        $fecha_inicio_contrato,
        $fecha_cierre,
        $ampliacion_monto,
        $reduccion_monto,
        $ampliacion_plazo,
        $reduccion_plazo,
        $diferimiento_periodo
    ]);

    $obra_id = $conn->lastInsertId();

    // Insertar periodos de supervisión (tabla aparte)
    if (!empty($_POST['mes'])) {
        foreach ($_POST['mes'] as $i => $mes) {
            $inicio = $_POST['fecha_inicio'][$i];
            $fin = $_POST['fecha_fin'][$i];
            if ($mes && $inicio && $fin) {
                $conn->prepare("INSERT INTO periodos_supervision (obra_id, mes, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?)")
                    ->execute([$obra_id, $mes, $inicio, $fin]);
            }
        }
    }

    // Insertar estimaciones si existen
    if (!empty($_POST['estimacion_numero'])) {
        foreach ($_POST['estimacion_numero'] as $i => $numero) {
            $del = $_POST['estimacion_del'][$i] ?? null;
            $al = $_POST['estimacion_al'][$i] ?? null;
            $monto = floatval($_POST['estimacion_monto'][$i] ?? 0);
            $cinco_millar = floatval($_POST['estimacion_cinco_millar'][$i] ?? 0);
            $amortizacion_anticipo = floatval($_POST['estimacion_amortizacion_anticipo'][$i] ?? 0);
            $liquidacion_pagar	 = floatval($_POST['estimacion_liquidacion_pagar'][$i] ?? 0);

            if ($numero && $del && $al) {
                $stmt = $conn->prepare("INSERT INTO estimaciones (
                obra_id, numero_estimacion, fecha_del, fecha_al, monto_civa,
                cinco_millar, amortizacion_anticipo, liquidacion_pagar	
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $obra_id,
                    $numero,
                    $del,
                    $al,
                    $monto,
                    $cinco_millar,
                    $amortizacion_anticipo,
                    $liquidacion_pagar	
                ]);
            }
        }
    }


    header("Location: registrar_obra.php?registro=ok");
    exit;
}

// Obtener obras existentes
$stmt = $conn->prepare("SELECT * FROM obras WHERE municipio_id = ? AND anio = ? ORDER BY fecha_creacion DESC");
$stmt->execute([$municipio_id, $anio]);
$obras = $stmt->fetchAll(PDO::FETCH_ASSOC);
