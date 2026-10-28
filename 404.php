<?php
require_once __DIR__ . '/includes/bootstrap.php';
http_response_code(404);
encabezado('Página no encontrada', ['rol' => null]);
?>
<section class="tarjeta tarjeta--acceso centro">
  <h1>Página no encontrada</h1>
  <p class="sutil">La dirección que buscó no existe o fue movida.</p>
  <p><a class="boton" href="<?= e(url('index.php')) ?>">Ir al inicio</a></p>
</section>
<?php
pie();
