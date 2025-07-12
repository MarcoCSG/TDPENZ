<?php
// El autoloader debe ir PRIMERO
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../config/aws.php';


// Obtener instancia configurada de S3
$s3 = getS3Client();
$bucket = getS3Bucket();

if (!isset($_GET['id'])) {
    echo "<p>Selecciona un periodo de supervisión desde el menú.</p>";
    return;
}

// Solo usar variables de sesión, nunca POST
$municipio_id = $_SESSION['municipio_id'];
$anio = $_SESSION['anio'];

$periodo_id = $_GET['id'];
$obra_id = $_SESSION['obra_id'];

// === OBTENER DATOS DE LA OBRA ===
$stmt = $conn->prepare("SELECT nombre, localidad, fuente_financiamiento, descripcion FROM obras WHERE id = ?");
$stmt->execute([$obra_id]);
$obra = $stmt->fetch(PDO::FETCH_ASSOC);

// === MUNICIPIO ===
$stmt = $conn->prepare("SELECT nombre FROM municipios WHERE id = ?");
$stmt->execute([$municipio_id]);
$municipio = $stmt->fetchColumn();
$obra['municipio'] = $municipio;

// === DATOS DEL PERIODO ===
$stmt = $conn->prepare("SELECT * FROM periodos_supervision WHERE id = ? AND obra_id = ?");
$stmt->execute([$periodo_id, $obra_id]);
$periodo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$periodo) {
    echo "<p>Periodo de supervisión no encontrado.</p>";
    return;
}

// === PROCESAR SUBIDA A S3 ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['imagenes'])) {
    foreach ($_FILES['imagenes']['tmp_name'] as $index => $tmp_name) {
        if ($_FILES['imagenes']['error'][$index] !== UPLOAD_ERR_OK) {
            continue;
        }

        $descripcion = $_POST['descripciones'][$index] ?? '';
        $nombre_archivo = uniqid() . '_' . basename($_FILES['imagenes']['name'][$index]);
        $ruta_s3 = "periodos_supervision/" . $nombre_archivo;

        try {
            $result = $s3->putObject([
                'Bucket' => $bucket,
                'Key' => $ruta_s3,
                'SourceFile' => $tmp_name,
                'ContentType' => mime_content_type($tmp_name)
            ]);

            // ✅ URL accesible directamente
            $url_imagen = $result['ObjectURL'];

            // Guarda la URL en la base de datos
            $stmt = $conn->prepare("INSERT INTO periodos_imagenes (periodo_id, ruta, descripcion) VALUES (?, ?, ?)");
            $stmt->execute([$periodo_id, $url_imagen, $descripcion]);

        } catch (\Aws\Exception\AwsException $e) {
            error_log("Error detallado S3: " . $e->getAwsErrorMessage());
            echo "<script>alert('Error al subir: " . addslashes($e->getAwsErrorMessage()) . "');</script>";
        }
    }
}


// === IMÁGENES GUARDADAS ===
$stmt = $conn->prepare("SELECT * FROM periodos_imagenes WHERE periodo_id = ?");
$stmt->execute([$periodo_id]);
$imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Fotográfico - Periodo <?= htmlspecialchars($periodo['mes']) ?></title>
    <link rel="stylesheet" href="assets/css/estimaciones.css">
</head>

<body>
    <div class="reporte-container">
        <h2>REPORTE FOTOGRÁFICO (ANEXO II)</h2>
        <p><strong>REPORTE FOTOGRÁFICO DEL INFORME DE SEGUIMIENTO DE OBRA PÚBLICA (PARTICIPACIÓN DEL SUPERVISOR EXTERNO EN LA OBRA PÚBLICA)</strong></p>
        <p><strong>EJERCICIO FISCAL:</strong> <?= htmlspecialchars($anio) ?></p>
        <p><strong>ENTE FISCALIZABLE:</strong> <?= htmlspecialchars($obra['municipio']) ?></p>
        <p><strong>LOCALIDAD:</strong> <?= htmlspecialchars($obra['localidad']) ?></p>
        <p><strong>No. DE OBRA:</strong> <?= htmlspecialchars($obra['nombre']) ?></p>
        <p><strong>DESCRIPCIÓN:</strong> <?= htmlspecialchars($obra['descripcion']) ?></p>
        <p><strong>FUENTE DE FINANCIAMIENTO:</strong> <?= htmlspecialchars($obra['fuente_financiamiento']) ?></p>

        <p><strong>PERIODO:</strong> <?= htmlspecialchars($periodo['mes']) ?> (<?= $periodo['fecha_inicio'] ?> al <?= $periodo['fecha_fin'] ?>)</p>

        <a target="_blank" href="secciones/generar_pdf_periodo.php?id_obra=<?= $obra_id ?>&id_periodo=<?= $periodo_id ?>" class="pdf-btn">📄 GENERAR PDF</a>

        <h3><?= htmlspecialchars($periodo['mes']) ?></h3>

        <form method="POST" enctype="multipart/form-data" class="imagenes-form">
            <div class="galeria">
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <div class="foto-item">
                        <label class="imagen-label">
                            <input type="file" name="imagenes[]" accept="image/*" onchange="mostrarVistaPrevia(this)" />
                            <div class="imagen-placeholder">
                                <img src="assets/img/subir-foto.png" alt="Subir" class="placeholder-img">
                                <img src="#" class="vista-previa" style="display: none;">
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
                            <button onclick="eliminarImagen(<?= $img['id'] ?>, this)">🗑️ Eliminar</button>
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
                fetch('secciones/eliminar_imagen_periodo.php', {
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

                fetch('secciones/reemplazar_imagen_periodo.php', {
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

</body>

</html>