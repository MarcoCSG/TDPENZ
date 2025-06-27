<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['usuario_id'], $_SESSION['municipio_id'], $_SESSION['anio'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['obra_id'])) {
    $_SESSION['obra_id'] = $_POST['obra_id'];
}

if (!isset($_SESSION['obra_id'])) {
    header("Location: seleccionar_obra.php");
    exit;
}

$obra_id = $_SESSION['obra_id'];

// Cargar información de la obra
$stmt = $conn->prepare("SELECT * FROM obras WHERE id = ?");
$stmt->execute([$obra_id]);
$obra = $stmt->fetch(PDO::FETCH_ASSOC);

$seccion = $_GET['seccion'] ?? 'general';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <link rel="icon" href="assets/img/logo_redondo.png" type="image/x-icon">
    <title>Obra: <?= htmlspecialchars($obra['nombre']) ?></title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>

<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-logo">
                <a href="menu.php">
                    <img src="assets/img/logo.png" alt="Logo Empresa">
                </a>
            </div>
            <div class="sidebar-header">
                <h2><?= htmlspecialchars($obra['nombre']) ?></h2>
                <a href="supervision_obras.php" class="btn-cambiar-obra">Cambiar de obra</a>
            </div>

            <ul>
                <li><a href="?seccion=general" class="<?= $seccion == 'general' ? 'active' : '' ?>">DATOS GENERALES</a></li>

                <!-- Periodos de Supervisión con submenú -->
                <li>
                    <details <?= $seccion == 'periodos_supervision' ? 'open' : '' ?>>
                        <summary class="<?= $seccion == 'periodos_supervision' ? 'active' : '' ?>">PERIODOS DE SUPERVISIÓN</summary>
                        <ul>
                            <?php
                            $stmt = $conn->prepare("SELECT id, mes FROM periodos_supervision WHERE obra_id = ? ORDER BY id ASC");
                            $stmt->execute([$obra_id]);
                            $periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($periodos as $p) {
                                echo "<li style='margin-left: 10px;'><a href='?seccion=periodos_supervision&id={$p['id']}'>" . htmlspecialchars($p['mes']) . "</a></li>";
                            }
                            ?>
                        </ul>
                    </details>
                </li>

                <!-- Estimaciones con submenú -->
                <li>
                    <details <?= $seccion == 'estimaciones' ? 'open' : '' ?>>
                        <summary class="<?= $seccion == 'estimaciones' ? 'active' : '' ?>">ESTIMACIONES</summary>
                        <ul>
                            <?php
                            $stmt = $conn->prepare("SELECT id, numero_estimacion FROM estimaciones WHERE obra_id = ? ORDER BY id ASC");
                            $stmt->execute([$obra_id]);
                            $estimaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($estimaciones as $e) {
                                echo "<li style='margin-left: 10px;'><a href='?seccion=estimaciones&id={$e['id']}'>Estimación #" . htmlspecialchars($e['numero_estimacion']) . "</a></li>";
                            }
                            ?>
                        </ul>
                    </details>
                </li>

                <!-- cédula con submenú -->
                <li>
                    <details <?= $seccion == 'cedula' ? 'open' : '' ?>>
                        <summary class="<?= $seccion == 'cedula' ? 'active' : '' ?>">CÉDULA DE ESTATUS DE ESTIMACIONES</summary>
                        <ul>
                            <?php
                            $stmt = $conn->prepare("SELECT id, numero_estimacion FROM estimaciones WHERE obra_id = ? ORDER BY id ASC");
                            $stmt->execute([$obra_id]);
                            $estimaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($estimaciones as $e) {
                                echo "<li style='margin-left: 10px;'><a href='?seccion=cedula&id={$e['id']}'>Estimación #" . htmlspecialchars($e['numero_estimacion']) . "</a></li>";
                            }
                            ?>
                        </ul>
                    </details>
                </li>

                <!-- MINUTA con submenú -->
                <li>
                    <details <?= $seccion == 'minuta' ? 'open' : '' ?>>
                        <summary class="<?= $seccion == 'minuta' ? 'active' : '' ?>">MINUTA INFORMATIVA DE AVANCE FÍSICO Y FINANCIERO</summary>
                        <ul>
                            <?php
                            $stmt = $conn->prepare("SELECT id, numero_estimacion FROM estimaciones WHERE obra_id = ? ORDER BY id ASC");
                            $stmt->execute([$obra_id]);
                            $estimaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($estimaciones as $e) {
                                echo "<li style='margin-left: 10px;'><a href='?seccion=minuta&id={$e['id']}'>Estimación #" . htmlspecialchars($e['numero_estimacion']) . "</a></li>";
                            }
                            ?>
                        </ul>
                    </details>
                </li>

                <!-- <li><a href="?seccion=minuta" class="<?= $seccion == 'minuta' ? 'active' : '' ?>">MINUTA INFORMATIVA DE AVANCE FÍSICO Y FINANCIERO</a></li> -->
                <li><a href="?seccion=dictamen" class="<?= $seccion == 'dictamen' ? 'active' : '' ?>">DICTAMEN DE PROCEDENCIA</a></li>

            </ul>
        </aside>

        <main class="main-content">
            <?php
            switch ($seccion) {
                case 'estimaciones':
                    include 'secciones/estimaciones.php';
                    break;
                case 'periodos_supervision':
                    include 'secciones/periodos_supervision.php';
                    break;
                case 'cedula':
                    include 'secciones/cedula.php';
                    break;
                case 'minuta':
                    include 'secciones/minuta.php';
                    break;
                case 'dictamen':
                    include 'secciones/dictamen.php';
                    break;
                default:
                    include 'secciones/general.php';
            }
            ?>
        </main>
    </div>
</body>

</html>