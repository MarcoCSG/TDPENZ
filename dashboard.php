<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Procesar formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['municipio_id'] = $_POST['municipio_id'];
    $_SESSION['anio'] = $_POST['anio'];
    header("Location: menu.php");
    exit;
}

// Obtener municipios asignados al usuario
$usuario_id = $_SESSION['usuario_id'];
$query = $conn->prepare("
    SELECT m.id, m.nombre 
    FROM municipios m
    JOIN usuario_municipio um ON um.municipio_id = m.id
    WHERE um.usuario_id = ?
");
$query->execute([$usuario_id]);
$municipios = $query->fetchAll(PDO::FETCH_ASSOC);

// Identificar si Santiago Tuxtla está entre los municipios
$santiagoTuxtlaId = null;
foreach ($municipios as $municipio) {
    if ($municipio['nombre'] === 'Municipio de Santigo Tuxtla, Ver.') {
        $santiagoTuxtlaId = $municipio['id'];
        break;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Seleccionar municipio</title>
    <link rel="icon" href="assets/img/logo_redondo.png" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <img src="assets/img/logo.png" class="logo" alt="Logo empresa">
        <h2>Selecciona un municipio y año</h2>

        <form action="dashboard.php" method="POST" id="municipioForm">
            <label for="municipio">Municipio:</label>
            <select name="municipio_id" id="municipio" required onchange="actualizarAnios()">
                <?php foreach ($municipios as $m): ?>
                    <option value="<?= $m['id'] ?>" data-name="<?= htmlspecialchars($m['nombre']) ?>">
                        <?= htmlspecialchars($m['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="anio">Año:</label>
            <select name="anio" id="anio" required>
                <!-- Las opciones se generarán dinámicamente con JavaScript -->
            </select>

            <button type="submit">Continuar</button>
        </form>
        
        <br>
        <a href="alta_municipio.php" class="alta-button">
            <img src="assets/img/supervisor.png" alt="supervision">
            REGISTRAR NUEVO MUNICIPIO
        </a>
    </div>

    <script>
        // Guardar el ID de Santiago Tuxtla para usarlo en JavaScript
        const santiagoTuxtlaId = <?= $santiagoTuxtlaId ? json_encode($santiagoTuxtlaId) : 'null' ?>;
        
        // Función para actualizar las opciones de año según el municipio seleccionado
        function actualizarAnios() {
            const municipioSelect = document.getElementById('municipio');
            const anioSelect = document.getElementById('anio');
            const selectedOption = municipioSelect.options[municipioSelect.selectedIndex];
            const municipioName = selectedOption.getAttribute('data-name');
            
            // Limpiar opciones actuales
            anioSelect.innerHTML = '';
            
            // Si es Santiago Tuxtla, agregar años especiales
            if (santiagoTuxtlaId && selectedOption.value == santiagoTuxtlaId) {
                const anosEspeciales = [2021, 2023];
                anosEspeciales.forEach(ano => {
                    const option = document.createElement('option');
                    option.value = ano;
                    option.textContent = ano;
                    anioSelect.appendChild(option);
                });
            }
            
            // Agregar años normales (2025-2029)
            for (let a = 2025; a <= 2029; a++) {
                const option = document.createElement('option');
                option.value = a;
                option.textContent = a;
                // Seleccionar 2025 por defecto
                if (a === 2025) {
                    option.selected = true;
                }
                anioSelect.appendChild(option);
            }
        }
        
        // Inicializar los años al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            actualizarAnios();
        });
    </script>
</body>
</html>