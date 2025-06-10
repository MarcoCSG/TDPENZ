<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registrar Municipio</title>
  <link rel="stylesheet" href="assets/css/alta_obra.css">
  <link rel="icon" href="assets/img/logo_redondo.png" type="image/x-icon">
</head>
<body>
  <div class="form-container">
    <h1>Registrar Municipio</h1>
    <form action="registrar_municipio.php" method="POST" enctype="multipart/form-data">
      <label for="municipio">Nombre del Municipio</label>
      <input type="text" name="municipio" id="municipio" required>

      <label for="logo">Logo de la Empresa</label>
      <input type="file" name="logo" id="logo" accept="image/*" onchange="previewLogo()" required>
      <div class="preview">
        <img id="logoPreview" src="#" alt="Vista previa del logo" style="display:none;">
      </div>

      <button type="submit">Registrar</button>
    </form>
  </div>
  <script src="script.js"></script>
</body>
</html>
<script>
    function previewLogo() {
  const input = document.getElementById('logo');
  const preview = document.getElementById('logoPreview');

  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function (e) {
      preview.src = e.target.result;
      preview.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
  }
}

</script>