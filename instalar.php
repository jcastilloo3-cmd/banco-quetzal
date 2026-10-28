<?php
/**
 * Instalador web de la base de datos — SOLO funciona desde la misma
 * computadora (localhost). Alternativa a importar el script en phpMyAdmin.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/instalador.php';

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
header('Content-Type: text/html; charset=utf-8');
if (!in_array($ip, ['127.0.0.1', '::1'], true) || getenv('DB_HOST')) {
    http_response_code(404);
    exit('No encontrado');
}
$forzar = ($_POST['forzar'] ?? '') === '1';
$resultado = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        $resultado = instalar_bd($forzar);
    } catch (Throwable $e) {
        $resultado = [false, $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"><title>Instalar base de datos</title>
<link rel="stylesheet" href="assets/css/estilos.css"></head>
<body><main class="contenido"><section class="tarjeta tarjeta--formulario">
<h1>Instalar base de datos</h1>
<p>Servidor: <code><?= htmlspecialchars(DB_HOST . ':' . DB_PORT) ?></code> · Base: <code><?= htmlspecialchars(DB_NAME) ?></code></p>
<?php if ($resultado): ?>
  <div class="alerta alerta--<?= $resultado[0] ? 'exito' : 'error' ?>"><?= htmlspecialchars($resultado[1]) ?></div>
<?php endif; ?>
<form method="post">
  <label><input type="checkbox" name="forzar" value="1" style="width:auto;min-height:0"> Borrar y reinstalar (deja la BD en blanco)</label>
  <p><button class="boton" type="submit">Instalar</button> <a class="boton boton--borde" href="index.php">Ir al sitio</a></p>
</form>
</section></main></body></html>
