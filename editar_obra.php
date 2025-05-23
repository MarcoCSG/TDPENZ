<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'admin') {
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $conn->prepare("UPDATE obras SET nombre=?, descripcion=?, fuente_financiamiento=?, localidad=?, latitud=?, longitud=? WHERE id=?");
    $stmt->execute([
        $_POST['nombre'], $_POST['descripcion'], $_POST['fuente'],
        $_POST['localidad'], $_POST['latitud'], $_POST['longitud'], $id
    ]);
    header("Location: obras.php?editado=ok");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Obra</title>
    <style>
        :root {
            --color-primary: #2b6cb0;
            --color-secondary: #4299e1;
            --color-light: #f8fafc;
            --color-text: #2d3748;
            --color-border: #e2e8f0;
            --color-success: #38a169;
            --color-error: #e53e3e;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 20px;
            color: var(--color-text);
            line-height: 1.5;
        }
        
        .main-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            padding: 30px;
        }
        
        .app-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--color-border);
        }
        
        .app-logo {
            max-height: 50px;
            width: auto;
            margin-bottom: 10px;
        }
        
        .page-title {
            font-size: 1.5rem;
            color: var(--color-primary);
            margin-bottom: 5px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 25px;
            color: var(--color-secondary);
            text-decoration: none;
            font-size: 0.875rem;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .form-container {
            margin-top: 20px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--color-text);
        }
        
        input, textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--color-border);
            border-radius: 4px;
            font-size: 0.875rem;
            transition: border 0.2s;
        }
        
        input:focus, textarea:focus {
            border-color: var(--color-secondary);
            outline: none;
            box-shadow: 0 0 0 2px rgba(66, 153, 225, 0.2);
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn {
            padding: 10px 20px;
            background-color: var(--color-secondary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
            margin-top: 10px;
        }
        
        .btn:hover {
            background-color: var(--color-primary);
            transform: translateY(-1px);
        }
        
        .coord-group {
            display: flex;
            gap: 15px;
        }
        
        .coord-group .form-group {
            flex: 1;
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 20px;
            }
            
            .coord-group {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <header class="app-header">
            <img src="assets/img/logo.png" class="app-logo" alt="Logo empresa">
            <h1 class="page-title">Editar Obra</h1>
        </header>

        <a href="registrar_obra.php" class="back-link">← Volver al listado de obras</a>

        <div class="form-container">
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
                
                <button type="submit" class="btn">Guardar Cambios</button>
            </form>
        </div>
    </div>
</body>
</html>