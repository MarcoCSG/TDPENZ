<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'admin') {
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION['municipio_id'])) {
    // Asegurarse que el municipio haya sido seleccionado antes
    die("No se ha seleccionado un municipio.");
}

// Guardar obra nueva si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $fuente = $_POST['fuente'];
    $localidad = $_POST['localidad'];
    $lat = $_POST['latitud'];
    $lng = $_POST['longitud'];
    $municipio_id = $_SESSION['municipio_id']; // <-- Tomamos el municipio activo

    // Insertar la obra incluyendo municipio_id
    $stmt = $conn->prepare("INSERT INTO obras (nombre, descripcion, fuente_financiamiento, localidad, latitud, longitud, municipio_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nombre, $descripcion, $fuente, $localidad, $lat, $lng, $municipio_id]);

    $obra_id = $conn->lastInsertId();

    // Insertar periodos
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

    header("Location: obras.php?registro=ok");
    exit;
}

// Obtener obras
$obras = $conn->query("SELECT * FROM obras ORDER BY fecha_creacion DESC")->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Obras</title>
    <link rel="stylesheet" href="assets/css/registrar_obras.css">
</head>
<body>
    <div class="main-container">
        <header class="app-header">
            <img src="assets/img/logo.png" class="app-logo" alt="Logo empresa">
            <h1>Registro de Obras</h1>
        </header>

        <?php if (isset($_GET['registro'])): ?>
            <div class="alert alert-success">✅ Obra registrada correctamente.</div>
        <?php endif; ?>

        <?php if (isset($_GET['editado'])): ?>
            <div class="alert alert-success">✏️ Obra editada correctamente.</div>
        <?php elseif (isset($_GET['eliminado'])): ?>
            <div class="alert alert-error">🗑️ Obra eliminada.</div>
        <?php endif; ?>

        <div class="form-container">
            <h2 class="form-title">Registrar Nueva Obra</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nombre">Nombre de la obra</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="localidad">Localidad</label>
                        <input type="text" id="localidad" name="localidad" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="fuente">Fuente de financiamiento</label>
                        <input type="text" id="fuente" name="fuente" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="latitud">Latitud</label>
                        <input type="text" id="latitud" name="latitud" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="longitud">Longitud</label>
                        <input type="text" id="longitud" name="longitud" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" required></textarea>
                </div>
                
                <div class="periodos-container">
                    <h3>Periodos de supervisión</h3>
                    <div id="periodos">
                        <div class="periodo-item">
                            <input type="text" name="mes[]" placeholder="Mes" required>
                            <input type="date" name="fecha_inicio[]" required>
                            <input type="date" name="fecha_fin[]" required>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="agregarPeriodo()">+ Añadir periodo</button>
                </div>
                
                <div class="form-group" style="margin-top: 20px;">
                    <button type="submit" class="btn">Registrar Obra</button>
                </div>
            </form>
        </div>
        
        <div class="table-container">
            <h2 class="form-title">Obras Registradas</h2>
            <table class="works-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Localidad</th>
                        <th>Coordenadas</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($obras as $obra): ?>
                        <tr>
                            <td><?= htmlspecialchars($obra['nombre']) ?></td>
                            <td><?= htmlspecialchars($obra['localidad']) ?></td>
                            <td><?= $obra['latitud'] ?>, <?= $obra['longitud'] ?></td>
                            <td><?= date('d/m/Y', strtotime($obra['fecha_creacion'])) ?></td>
                            <td>
                                <a href="editar_obra.php?id=<?= $obra['id'] ?>" class="action-link">✏️ Editar</a>
                                <a href="eliminar_obra.php?id=<?= $obra['id'] ?>" class="action-link delete" onclick="return confirm('¿Eliminar esta obra?')">🗑️ Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function agregarPeriodo() {
            const div = document.createElement('div');
            div.className = 'periodo-item';
            div.innerHTML = `
                <input type="text" name="mes[]" placeholder="Mes" required>
                <input type="date" name="fecha_inicio[]" required>
                <input type="date" name="fecha_fin[]" required>
            `;
            document.getElementById('periodos').appendChild(div);
        }
    </script>
</body>
</html>