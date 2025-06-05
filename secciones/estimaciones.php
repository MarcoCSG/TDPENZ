<?php
if (!isset($_GET['id'])) {
    echo "<p>Selecciona una estimación desde el menú.</p>";
    return;
}

// Solo usar variables de sesión, nunca POST
$municipio_id = $_SESSION['municipio_id'];
$anio = $_SESSION['anio'];

$estimacion_id = $_GET['id'];
$obra_id = $_SESSION['obra_id'];

// Obtener info de la obra
$stmt = $conn->prepare("SELECT nombre, localidad, fuente_financiamiento, descripcion FROM obras WHERE id = ?");
$stmt->execute([$obra_id]);
$obra = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener nombre del municipio seleccionado
$stmt = $conn->prepare("SELECT nombre FROM municipios WHERE id = ?");
$stmt->execute([$municipio_id]);
$municipio = $stmt->fetchColumn();
$obra['municipio'] = $municipio;

// Obtener info de la estimación
$stmt = $conn->prepare("SELECT * FROM estimaciones WHERE id = ? AND obra_id = ?");
$stmt->execute([$estimacion_id, $obra_id]);
$estimacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$estimacion) {
    echo "<p>Estimación no encontrada.</p>";
    return;
}

// Procesar subida de imagen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['imagenes'])) {
    foreach ($_FILES['imagenes']['tmp_name'] as $index => $tmp_name) {
        $descripcion = $_POST['descripciones'][$index] ?? '';
        $nombre_archivo = $_FILES['imagenes']['name'][$index];
        $ruta_destino = "uploads/estimaciones/" . basename($nombre_archivo);

        if (!is_dir("uploads/estimaciones")) {
            mkdir("uploads/estimaciones", 0777, true);
        }

        if (move_uploaded_file($tmp_name, $ruta_destino)) {
            $stmt = $conn->prepare("INSERT INTO estimacion_imagenes (estimacion_id, ruta, descripcion) VALUES (?, ?, ?)");
            $stmt->execute([$estimacion_id, $ruta_destino, $descripcion]);
        }
    }
}

// Obtener imágenes cargadas
$stmt = $conn->prepare("SELECT * FROM estimacion_imagenes WHERE estimacion_id = ?");
$stmt->execute([$estimacion_id]);
$imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="reporte-container">
    <h2>REPORTE FOTOGRÁFICO (ANEXO II)</h2>
    <p><strong>REPORTE FOTOGRÁFICO DEL INFORME DE LA REVISIÓN DE ESTIMACIONES (PARTICIPACIÓN DEL SUPERVISOR EXTERNO EN LA OBRA PÚBLICA)</strong></p>
    <p><strong>EJERCICIO FISCAL:</strong> 2025</p>
    <p><strong>ENTE FISCALIZABLE:</strong> <?= htmlspecialchars($obra['municipio']) ?></p>
    <p><strong>LOCALIDAD:</strong> <?= htmlspecialchars($obra['localidad']) ?></p>
    <p><strong>No. DE OBRA:</strong> <?= htmlspecialchars($obra['nombre']) ?></p>

    <p><strong>DESCRIPCIÓN:</strong> <?= htmlspecialchars($obra['descripcion']) ?></p>
    <p><strong>FUENTE DE FINANCIAMIENTO:</strong> <?= htmlspecialchars($obra['fuente_financiamiento']) ?></p>

    <a href="secciones/generar_pdf.php?id=<?= $estimacion_id ?>" class="pdf-btn">📄 GENERAR PDF</a>

    <h3><?= htmlspecialchars($estimacion['numero_estimacion']) ?> (<?= ucfirst(strtolower($estimacion['tipo'] ?? 'NORMAL')) ?>)</h3>

<form method="POST" enctype="multipart/form-data" class="imagenes-form">
        <div class="galeria">
            <?php for ($i = 0; $i < 9; $i++): ?>
                <div class="foto-item">
                    <label class="imagen-label">
                        <input type="file" name="imagenes[]" accept="image/*" class="imagen-input" onchange="mostrarVistaPrevia(this)" />
                        <div class="imagen-placeholder">
                            <img src="/TDPENZ/assets/img/subir-foto.png" alt="Subir foto" class="placeholder-img">
                            <img src="#" alt="Vista previa" class="vista-previa" style="display: none;">
                        </div>
                    </label>
                    <input type="text" name="descripciones[]" placeholder="DESCRIPCIÓN">
                </div>
            <?php endfor; ?>
        </div>
        <button type="submit" class="btn-guardar">GUARDAR</button>
    </form>

    <h3>IMÁGENES GUARDADAS</h3>
    <div class="galeria" id="galeria-guardadas">
        <?php foreach ($imagenes as $img): ?>
            <div class="foto-item-guardada" data-id="<?= $img['id'] ?>">
                <div class="imagen-container">
                    <img src="<?= htmlspecialchars($img['ruta']) ?>" alt="Imagen guardada">
                    <div class="acciones-imagen">
                        <button class="btn-eliminar" onclick="eliminarImagen(<?= $img['id'] ?>, this)">🗑️ Eliminar</button>
                        <label class="btn-reemplazar">
                            <input type="file" class="input-reemplazar" accept="image/*" onchange="reemplazarImagen(this, <?= $img['id'] ?>)">
                            ✏️ Reemplazar
                        </label>
                    </div>
                </div>
                <p><?= htmlspecialchars($img['descripcion']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    function mostrarVistaPrevia(input) {
        const item = input.closest('.foto-item');
        const placeholder = item.querySelector('.placeholder-img');
        const vistaPrevia = item.querySelector('.vista-previa');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                vistaPrevia.src = e.target.result;
                vistaPrevia.style.display = 'block';
                vistaPrevia.style.objectFit = 'cover';
                placeholder.style.display = 'none';
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }

    function eliminarImagen(id, boton) {
        if (confirm('¿Estás seguro de eliminar esta imagen?')) {
            fetch('secciones/eliminar_imagen.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    boton.closest('.foto-item-guardada').remove();
                } else {
                    alert('Error al eliminar la imagen');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al eliminar la imagen');
            });
        }
    }

    function reemplazarImagen(input, id) {
        if (input.files && input.files[0]) {
            const formData = new FormData();
            formData.append('imagen', input.files[0]);
            formData.append('id', id);
            
            fetch('secciones/reemplazar_imagen.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const contenedor = input.closest('.foto-item-guardada');
                    contenedor.querySelector('img').src = data.nuevaRuta;
                    alert('Imagen reemplazada correctamente');
                } else {
                    alert('Error al reemplazar la imagen');
                input.value = '';
                console.error(data.error);
                console.error(data.debug);
                console.error(data.trace);
                console.error(data.message);
                console.error(data.code);
                console.error(data.file);
                console.error(data.line);
                console.error(data.context);
                console.error(data.stack);
                console.error(data.previous);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al reemplazar la imagen');
            input.value = '';
        });
    }
}
</script>

<style>
    /* Estilos existentes... */
    .foto-item-guardada {
        position: relative;
        background: #f0f0f0;
        padding: 15px;
        border-radius: 12px;
        text-align: center;
    }
    
    .imagen-container {
        position: relative;
        width: 100%;
        height: 200px;
        margin-bottom: 10px;
    }
    
    .foto-item-guardada img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 10px;
    }
    
    .acciones-imagen {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0,0,0,0.7);
        padding: 5px;
        display: flex;
        justify-content: space-around;
        border-bottom-left-radius: 10px;
        border-bottom-right-radius: 10px;
    }
    
    .btn-eliminar, .btn-reemplazar {
        background: transparent;
        border: none;
        color: white;
        cursor: pointer;
        padding: 5px;
        font-size: 12px;
    }
    
    .btn-reemplazar {
        position: relative;
    }
    
    .input-reemplazar {
        position: absolute;
        opacity: 0;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        cursor: pointer;
    }
    
    /* Mejoras para las vistas previas */
    .imagen-placeholder {
        width: 100%;
        height: 200px;
        background: #ddd;
        border-radius: 10px;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: hidden;
    }
    
    .vista-previa {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: none;
    }
    
    .placeholder-img {
        width: 60px;
        opacity: 0.5;
    }
</style>

<style>
    .reporte-container {
        max-width: 1000px;
        margin: auto;
        padding: 20px;
        font-family: 'Arial', sans-serif;
    }

    h2,
    h3 {
        text-align: center;
    }

    .pdf-btn {
        float: right;
        background: #cfc04d;
        padding: 10px 15px;
        color: #000;
        text-decoration: none;
        border-radius: 10px;
        font-weight: bold;
    }

    .galeria {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin: 30px 0;
    }

    .foto-item {
        background: #f0f0f0;
        padding: 15px;
        border-radius: 12px;
        text-align: center;
    }

    .foto-item input[type="text"] {
        margin-top: 10px;
        width: 100%;
        padding: 6px;
        border-radius: 6px;
        border: 1px solid #ccc;
    }

    .imagen-label {
        display: block;
        cursor: pointer;
    }

    .imagen-input {
        display: none;
    }

    .imagen-placeholder {
        width: 100%;
        height: 160px;
        background: #ddd;
        border-radius: 10px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .imagen-placeholder img {
        width: 60px;
        opacity: 0.5;
    }

    .btn-guardar {
        background: #a8e5b1;
        padding: 10px 30px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: bold;
        display: block;
        margin: 0 auto;
        cursor: pointer;
    }
</style>