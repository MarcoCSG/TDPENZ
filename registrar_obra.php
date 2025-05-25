<?php require_once __DIR__ . '/controllers/registrar_obra.php'; ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/img/logo_redondo.png" type="image/x-icon">
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
                        <input type="number" id="latitud" name="latitud" step="any" required>
                    </div>

                    <div class="form-group">
                        <label for="longitud">Longitud</label>
                        <input type="number" id="longitud" name="longitud" step="any" required>
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

                <div class="datos-contratacion">
                    <h3>Datos de contratación de la obra</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="contratista">Contratista</label>
                            <input type="text" id="contratista" name="contratista">
                        </div>
                        <div class="form-group">
                            <label for="numero_contrato">Número de contrato</label>
                            <input type="text" id="numero_contrato" name="numero_contrato">
                        </div>
                        <div class="form-group">
                            <label for="porcentaje_anticipo">% de anticipo</label>
                            <input type="number" id="porcentaje_anticipo" name="porcentaje_anticipo" step="any">
                        </div>
                        <div class="form-group">
                            <label for="monto_contratado">Monto contratado</label>
                            <input type="number" id="monto_contratado" name="monto_contratado" step="any">
                        </div>
                        <div class="form-group">
                            <label for="tipo_adjudicacion">Tipo de adjudicación</label>
                            <input type="text" id="tipo_adjudicacion" name="tipo_adjudicacion">
                        </div>
                        <div class="form-group">
                            <label for="anticipo">Anticipo</label>
                            <input type="number" id="anticipo" name="anticipo" step="any">
                        </div>
                    </div>

                    <h3>Fechas de contratación</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="fecha_firma">Fecha de firma</label>
                            <input type="date" id="fecha_firma" name="fecha_firma">
                        </div>
                        <div class="form-group">
                            <label for="fecha_inicio">Fecha de inicio</label>
                            <input type="date" id="fecha_inicio" name="fecha_inicio_contrato">
                        </div>
                        <div class="form-group">
                            <label for="fecha_cierre">Fecha de cierre</label>
                            <input type="date" id="fecha_cierre" name="fecha_cierre">
                        </div>
                    </div>
                </div>

                <div class="datos-convenios">
                    <h3>Datos de convenios de la obra</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="ampliacion_monto">Ampliación del monto</label>
                            <input type="number" id="ampliacion_monto" name="ampliacion_monto" step="any">
                        </div>
                        <div class="form-group">
                            <label for="reduccion_monto">Reducción de monto</label>
                            <input type="number" id="reduccion_monto" name="reduccion_monto" step="any">
                        </div>
                        <div class="form-group">
                            <label for="ampliacion_plazo">Ampliación de plazo</label>
                            <input type="text" id="ampliacion_plazo" name="ampliacion_plazo">
                        </div>
                        <div class="form-group">
                            <label for="reduccion_plazo">Reducción de plazo</label>
                            <input type="text" id="reduccion_plazo" name="reduccion_plazo">
                        </div>
                        <div class="form-group">
                            <label for="diferimiento_periodo">Diferimiento del periodo contractual</label>
                            <input type="text" id="diferimiento_periodo" name="diferimiento_periodo">
                        </div>
                    </div>
                </div>

                <h4>Datos de la Estimación</h4>
                <div class="table-container">
                    <table id="tabla_estimaciones" class="estimaciones-table">
                        <thead>
                            <tr>
                                <th>NÚMERO DE ESTIMACIÓN</th>
                                <th>DEL</th>
                                <th>AL</th>
                                <th>MONTO C/IVA</th>
                                <th>5 AL MILLAR</th>
                                <th>AMORT. ANT.</th>
                                <th>LIQ. PAGAR</th>
                            </tr>
                        </thead>
                        <tbody id="estimaciones_body">
                            <!-- Filas generadas por JS -->
                        </tbody>
                    </table>
                </div>

                <br />

                <button type="button" class="btn-agregar" onclick="agregarFilaEstimacion()">
                    + Agregar Estimación
                </button>

                <script>
                    let contadorEstimacion = 0;

                    function agregarFilaEstimacion() {
                        const tbody = document.getElementById('estimaciones_body');
                        const row = document.createElement('tr');

                        row.innerHTML = `
    <td><input type="text" name="estimacion_numero[]" class="form-control" /></td>
    <td><input type="date" name="estimacion_del[]" class="form-control" /></td>
    <td><input type="date" name="estimacion_al[]" class="form-control" /></td>
    <td><input type="number" step="0.01" name="estimacion_monto[]" class="form-control" oninput="calcularEstimacion(this)" /></td>
    <td><input type="text" name="estimacion_cinco_millar[]" class="form-control" readonly /></td>
    <td><input type="text" name="estimacion_amortizacion_anticipo[]" class="form-control" readonly /></td>
    <td><input type="text" name="estimacion_liquidacion_pagar[]" class="form-control" readonly /></td>
`;


                        tbody.appendChild(row);
                        contadorEstimacion++;
                    }

                    // Cálculo automático
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

                    // Crear 5 filas por defecto al cargar
                    for (let i = 0; i < 3; i++) {
                        agregarFilaEstimacion();
                    }
                </script>



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