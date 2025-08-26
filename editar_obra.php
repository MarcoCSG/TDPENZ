<?php
session_start();
include 'includes/db.php';

// Verificar si usuario está logueado y tiene las variables de sesión necesarias
if (!isset($_SESSION['usuario_id'], $_SESSION['municipio_id'], $_SESSION['anio'])) {
    header("Location: index.php");
    exit;
}

// Obtener el tipo de usuario desde la base de datos
$stmt = $conn->prepare("SELECT tipo_usuario_id FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$tipo_usuario_id = $stmt->fetchColumn();

// Solo permitir acceso si es administrador (tipo_usuario_id = 1)
if ($tipo_usuario_id != 1) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) die("ID no válido.");

// Obtener obra
$stmt = $conn->prepare("SELECT * FROM obras WHERE id = ?");
$stmt->execute([$id]);
$obra = $stmt->fetch();
if (!$obra) die("Obra no encontrada.");

// Obtener periodos de supervisión
$stmt = $conn->prepare("SELECT * FROM periodos_supervision WHERE obra_id = ?");
$stmt->execute([$id]);
$periodos = $stmt->fetchAll();

// Obtener estimaciones
$stmt = $conn->prepare("SELECT * FROM estimaciones WHERE obra_id = ?");
$stmt->execute([$id]);
$estimaciones = $stmt->fetchAll();


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $conn->prepare("UPDATE obras SET nombre=?, descripcion=?, fuente_financiamiento=?, localidad=?, latitud=?, longitud=?, 
    contratista=?, numero_contrato=?, porcentaje_anticipo=?, monto_contratado=?, tipo_adjudicacion=?, anticipo=?, 
    fecha_firma=?, fecha_inicio_contrato=?, fecha_cierre=?, 
    ampliacion_monto=?, reduccion_monto=?, ampliacion_plazo=?, reduccion_plazo=?, diferimiento_periodo=?, 
    nombre_supervisor=?
    WHERE id=?");

    $stmt->execute([
        $_POST['nombre'],
        $_POST['descripcion'],
        $_POST['fuente'],
        $_POST['localidad'],
        $_POST['latitud'],
        $_POST['longitud'],
        $_POST['contratista'],
        $_POST['numero_contrato'],
        $_POST['porcentaje_anticipo'],
        $_POST['monto_contratado'],
        $_POST['tipo_adjudicacion'],
        $_POST['anticipo'],
        $_POST['fecha_firma'],
        $_POST['fecha_inicio_contrato'],
        $_POST['fecha_cierre'],
        $_POST['ampliacion_monto'],
        $_POST['reduccion_monto'],
        $_POST['ampliacion_plazo'],
        $_POST['reduccion_plazo'],
        $_POST['diferimiento_periodo'],
        $_POST['nombre_supervisor'],
        $id
    ]);

    // Procesar periodos existentes
    if (isset($_POST['periodo_id'])) {
        foreach ($_POST['periodo_id'] as $i => $pid) {
            $mes = $_POST['mes'][$i];
            $inicio = $_POST['fecha_inicio'][$i];
            $fin = $_POST['fecha_fin'][$i];

            if ($pid != '') {
                // Actualizar periodo existente
                $conn->prepare("UPDATE periodos_supervision SET mes=?, fecha_inicio=?, fecha_fin=? WHERE id=?")
                    ->execute([$mes, $inicio, $fin, $pid]);
            } elseif ($mes && $inicio && $fin) {
                // Nuevo periodo
                $conn->prepare("INSERT INTO periodos_supervision (obra_id, mes, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?)")
                    ->execute([$id, $mes, $inicio, $fin]);
            }
        }
    }

    // Eliminar periodos marcados
    if (isset($_POST['eliminar'])) {
        foreach ($_POST['eliminar'] as $eliminar_id) {
            $conn->prepare("DELETE FROM periodos_supervision WHERE id = ?")->execute([$eliminar_id]);
        }
    }

    // Procesar estimaciones
    if (isset($_POST['estimacion_numero'])) {
        foreach ($_POST['estimacion_numero'] as $i => $numero) {
            $estimacion_id = $_POST['estimacion_id'][$i] ?? null;

            $del = $_POST['estimacion_del'][$i];
            $al = $_POST['estimacion_al'][$i];
            $monto = $_POST['estimacion_monto'][$i];
            $cinco = $_POST['estimacion_cinco_millar'][$i];
            $amort = $_POST['estimacion_amortizacion_anticipo'][$i];
            $liq = $_POST['estimacion_liquidacion_pagar'][$i];

            if ($estimacion_id != '') {
                // Actualizar estimación existente
                $conn->prepare("UPDATE estimaciones SET numero_estimacion=?, fecha_del=?, fecha_al=?, monto_civa=?, cinco_millar=?, amortizacion_anticipo=?, liquidacion_pagar=? WHERE id=?")
                    ->execute([$numero, $del, $al, $monto, $cinco, $amort, $liq, $estimacion_id]);
            } elseif ($numero && $del && $al && $monto) {
                // Insertar nueva estimación
                $conn->prepare("INSERT INTO estimaciones (obra_id, numero_estimacion, fecha_del, fecha_al, monto_civa, cinco_millar, amortizacion_anticipo, liquidacion_pagar) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                    ->execute([$id, $numero, $del, $al, $monto, $cinco, $amort, $liq]);
            }
        }
    }

    // Eliminar estimaciones
    if (isset($_POST['eliminar_estimacion'])) {
        foreach ($_POST['eliminar_estimacion'] as $eid) {
            $conn->prepare("DELETE FROM estimaciones WHERE id = ?")->execute([$eid]);
        }
    }


    header("Location: editar_obra.php?id=$id&editado=ok");

    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Obra</title>
    <link rel="stylesheet" href="assets/css/editar_obra.css">
</head>

<body>
    <div class="main-container">
        <header class="app-header">
            <a href="menu.php">
                <img src="assets/img/logo.png" class="app-logo" alt="Logo empresa">
            </a>
            <h1 class="page-title">Editar Obra</h1>
        </header>


        <a href="registrar_obra.php" class="back-link">← Volver al listado de obras</a>

        <div class="form-container">
            <?php if (isset($_GET['editado']) && $_GET['editado'] === 'ok'): ?>
                <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border-radius: 4px; border: 1px solid #c3e6cb;">
                    Cambios guardados correctamente.
                </div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nombre">Nombre de la obra</label>
                        <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($obra['nombre']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="localidad">Localidad</label>
                        <input type="text" id="localidad" name="localidad" value="<?= htmlspecialchars($obra['localidad']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="fuente">Fuente de financiamiento</label>
                        <input type="text" id="fuente" name="fuente" value="<?= htmlspecialchars($obra['fuente_financiamiento']) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" required><?= htmlspecialchars($obra['descripcion']) ?></textarea>
                </div>

                <div class="coord-group">
                    <div class="form-group">
                        <label for="latitud">Latitud</label>
                        <input type="text" id="latitud" name="latitud" value="<?= htmlspecialchars($obra['latitud']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="longitud">Longitud</label>
                        <input type="text" id="longitud" name="longitud" value="<?= htmlspecialchars($obra['longitud']) ?>" required>
                    </div>
                </div>

                <h3>Periodos de Supervisión</h3>
                <div id="periodos">
                    <?php foreach ($periodos as $i => $p): ?>
                        <div class="form-grid periodo-item">
                            <input type="hidden" name="periodo_id[]" value="<?= $p['id'] ?>">
                            <div class="form-group">
                                <label>Mes</label>
                                <input type="text" name="mes[]" value="<?= htmlspecialchars($p['mes']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Fecha inicio</label>
                                <input type="date" name="fecha_inicio[]" value="<?= $p['fecha_inicio'] ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Fecha fin</label>
                                <input type="date" name="fecha_fin[]" value="<?= $p['fecha_fin'] ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Eliminar</label><br>
                                <input type="checkbox" name="eliminar[]" value="<?= $p['id'] ?>"> Eliminar
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <!-- Template para agregar nuevos -->
                <button type="button" onclick="agregarPeriodo()" class="btn">+ Agregar periodo</button>
                <script>
                    function agregarPeriodo() {
                        const cont = document.getElementById('periodos');
                        const div = document.createElement('div');
                        div.classList.add('form-grid');
                        div.innerHTML = `
        <input type="hidden" name="periodo_id[]" value="">
        <div class="form-group">
            <label>Mes</label>
            <input type="text" name="mes[]" required>
        </div>
        <div class="form-group">
            <label>Fecha inicio</label>
            <input type="date" name="fecha_inicio[]" required>
        </div>
        <div class="form-group">
            <label>Fecha fin</label>
            <input type="date" name="fecha_fin[]" required>
        </div>
    `;
                        cont.appendChild(div);
                    }
                </script>

                <h3>Estimaciones</h3>
                <div class="table-container">
                    <table id="tabla_estimaciones" class="estimaciones-table">
                        <thead>
                            <tr>
                                <th>NÚMERO</th>
                                <th>DEL</th>
                                <th>AL</th>
                                <th>MONTO C/IVA</th>
                                <th>5 AL MILLAR</th>
                                <th>AMORT. ANT.</th>
                                <th>LIQ. PAGAR</th>
                                <th>Eliminar</th>
                            </tr>
                        </thead>
                        <tbody id="estimaciones_body">
                            <?php foreach ($estimaciones as $e): ?>
                                <tr>
                                    <input type="hidden" name="estimacion_id[]" value="<?= $e['id'] ?>">
                                    <td><input type="text" name="estimacion_numero[]" value="<?= $e['numero_estimacion'] ?>"></td>
                                    <td><input type="date" name="estimacion_del[]" value="<?= $e['fecha_del'] ?>"></td>
                                    <td><input type="date" name="estimacion_al[]" value="<?= $e['fecha_al'] ?>"></td>
                                    <td><input type="number" step="0.01" name="estimacion_monto[]" value="<?= htmlspecialchars($e['monto_civa']) ?>" oninput="calcularEstimacion(this)"></td>
                                    <td><input type="text" name="estimacion_cinco_millar[]" value="<?= $e['cinco_millar'] ?>" ></td>
                                    <td><input type="text" name="estimacion_amortizacion_anticipo[]" value="<?= $e['amortizacion_anticipo'] ?>" ></td>
                                    <td><input type="text" name="estimacion_liquidacion_pagar[]" value="<?= $e['liquidacion_pagar'] ?>" ></td>
                                    <td><input type="checkbox" name="eliminar_estimacion[]" value="<?= $e['id'] ?>"></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <button type="button" class="btn" onclick="agregarFilaEstimacion()">+ Agregar Estimación</button>


                <h3>Datos de contratación de la obra</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Contratista</label>
                        <input type="text" name="contratista" value="<?= htmlspecialchars($obra['contratista']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Número de contrato</label>
                        <input type="text" name="numero_contrato" value="<?= htmlspecialchars($obra['numero_contrato']) ?>">
                    </div>
                    <div class="form-group">
                        <label>% de anticipo</label>
                        <input type="number" step="any" name="porcentaje_anticipo" value="<?= htmlspecialchars($obra['porcentaje_anticipo']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Monto contratado</label>
                        <input type="number" step="any" name="monto_contratado" value="<?= htmlspecialchars($obra['monto_contratado']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Tipo de adjudicación</label>
                        <input type="text" name="tipo_adjudicacion" value="<?= htmlspecialchars($obra['tipo_adjudicacion']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Anticipo</label>
                        <input type="number" step="any" name="anticipo" value="<?= htmlspecialchars($obra['anticipo']) ?>">
                    </div>
                </div>

                <h3>Fechas de contratación</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Fecha de firma</label>
                        <input type="date" name="fecha_firma" value="<?= htmlspecialchars($obra['fecha_firma']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Fecha de inicio</label>
                        <input type="date" name="fecha_inicio_contrato" value="<?= htmlspecialchars($obra['fecha_inicio_contrato']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Fecha de cierre</label>
                        <input type="date" name="fecha_cierre" value="<?= htmlspecialchars($obra['fecha_cierre']) ?>">
                    </div>
                </div>

                <h3>Datos de convenios de la obra</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Ampliación del monto</label>
                        <input type="number" step="any" name="ampliacion_monto" value="<?= htmlspecialchars($obra['ampliacion_monto']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Reducción de monto</label>
                        <input type="number" step="any" name="reduccion_monto" value="<?= htmlspecialchars($obra['reduccion_monto']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Ampliación de plazo</label>
                        <input type="text" name="ampliacion_plazo" value="<?= htmlspecialchars($obra['ampliacion_plazo']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Reducción de plazo</label>
                        <input type="text" name="reduccion_plazo" value="<?= htmlspecialchars($obra['reduccion_plazo']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Diferimiento del periodo contractual</label>
                        <input type="text" name="diferimiento_periodo" value="<?= htmlspecialchars($obra['diferimiento_periodo']) ?>">
                    </div>
                </div>

                <h3>Supervisor Externo</h3>
                <div class="form-group">
                    <label>Nombre del supervisor</label>
                    <input type="text" name="nombre_supervisor" value="<?= htmlspecialchars($obra['nombre_supervisor']) ?>" required>
                </div>

                <button type="submit" class="btn">Guardar Cambios</button>
            </form>
        </div>
    </div>
</body>

<script>
    function calcularEstimacion(input) {
        const row = input.closest('tr');
        const monto = parseFloat(input.value) || 0;

        const cincoMillar = (monto / 1.16) * 0.005;
        const amortAnt = monto * 0.3;
        const liqPagar = monto - cincoMillar - amortAnt;

        row.querySelector('input[name="estimacion_cinco_millar[]"]').value = cincoMillar.toFixed(2);
        row.querySelector('input[name="estimacion_amortizacion_anticipo[]"]').value = amortAnt.toFixed(2);
        row.querySelector('input[name="estimacion_liquidacion_pagar[]"]').value = liqPagar.toFixed(2);
    }

    function agregarFilaEstimacion() {
        const tbody = document.getElementById('estimaciones_body');
        const row = document.createElement('tr');

        row.innerHTML = `
            <input type="hidden" name="estimacion_id[]" value="">
            <td><input type="text" name="estimacion_numero[]"></td>
            <td><input type="date" name="estimacion_del[]"></td>
            <td><input type="date" name="estimacion_al[]"></td>
            <td><input type="number" step="0.01" name="estimacion_monto[]" oninput="calcularEstimacion(this)"></td>
            <td><input type="text" name="estimacion_cinco_millar[]" ></td>
            <td><input type="text" name="estimacion_amortizacion_anticipo[]" ></td>
            <td><input type="text" name="estimacion_liquidacion_pagar[]" ></td>
            <td><input type="checkbox" disabled></td>

        `;

        tbody.appendChild(row);
    }
</script>


</html>