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
    header("Location: seleccionar_obra.php"); // o el nombre de tu archivo anterior
    exit;
}

$obra_id = $_SESSION['obra_id'];

// Puedes cargar más info de la obra si deseas
$stmt = $conn->prepare("SELECT * FROM obras WHERE id = ?");
$stmt->execute([$obra_id]);
$obra = $stmt->fetch(PDO::FETCH_ASSOC);
$seccion = $_GET['seccion'] ?? 'general';

// Obtener periodos de supervisión
$stmt = $conn->prepare("SELECT id, mes FROM periodos_supervision WHERE obra_id = ? ORDER BY id ASC");
$stmt->execute([$obra_id]);
$periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estimaciones
$stmt = $conn->prepare("SELECT id, numero_estimacion FROM estimaciones WHERE obra_id = ? ORDER BY id ASC");
$stmt->execute([$obra_id]);
$estimaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            <h2><?= htmlspecialchars($obra['nombre']) ?></h2>
            <ul>
                <li><a href="?seccion=general" class="<?= $seccion == 'general' ? 'active' : '' ?>">Datos Generales</a></li>

                <!-- Periodos de Supervisión con submenú -->
                <li>
                    <details <?= $seccion == 'periodos_supervision' ? 'open' : '' ?>>
                        <summary class="<?= $seccion == 'periodos_supervision' ? 'active' : '' ?>">Periodos de Supervisión</summary>
                        <ul>
                            <?php
                            $stmt = $conn->prepare("SELECT id, mes FROM periodos_supervision WHERE obra_id = ?");
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
                        <summary class="<?= $seccion == 'estimaciones' ? 'active' : '' ?>">Estimaciones</summary>
                        <ul>
                            <?php
                            $stmt = $conn->prepare("SELECT id, numero_estimacion FROM estimaciones WHERE obra_id = ?");
                            $stmt->execute([$obra_id]);
                            $estimaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($estimaciones as $e) {
                                echo "<li style='margin-left: 10px;'><a href='?seccion=estimaciones&id={$e['id']}'>Estimación #" . htmlspecialchars($e['numero_estimacion']) . "</a></li>";
                            }
                            ?>
                        </ul>
                    </details>
                </li>

                <li><a href="?seccion=fotos" class="<?= $seccion == 'fotos' ? 'active' : '' ?>">Reportes Fotográficos</a></li>
                <li><a href="?seccion=documentos" class="<?= $seccion == 'documentos' ? 'active' : '' ?>">Documentación</a></li>
            </ul>


        </aside>

        <main class="main-content">
            <?php
            $seccion = $_GET['seccion'] ?? 'general';

            switch ($seccion) {
                case 'estimaciones':
                    include 'secciones/estimaciones.php';
                    break;
                case 'periodos_supervision':
                    include 'secciones/periodos_supervision.php';
                    break;
                case 'fotos':
                    include 'secciones/fotos.php';
                    break;
                case 'documentos':
                    include 'secciones/documentos.php';
                    break;
                default:
                    include 'secciones/general.php';
            }
            ?>
        </main>
    </div>
</body>

</html>