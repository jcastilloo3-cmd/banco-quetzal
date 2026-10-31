<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$auth = requiere_rol('usuario');

$errores = [];
$exito = '';
$sel = 0;
$montoTxt = '';

if (es_post()) {
    $sel = (int) filter_var($_POST['tercero'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $montoTxt = campo('monto');
    $monto = monto_valido($montoTxt);

    if (!csrf_valido()) {
        $errores[] = 'La sesión del formulario expiró. Recargue la página e intente de nuevo.';
    }
    if ($sel < 1) {
        $errores[] = 'Seleccione la cuenta de tercero a la que desea transferir.';
    }
    if ($monto === null) {
        $errores[] = 'El monto a transferir debe ser mayor que cero, con máximo 2 decimales.';
    }

    if (!$errores) {
        try {
            $r = sp_uno('sp_transferir', [$auth['id'], $sel, $monto], 'iis');
            $exito = 'Transferencia No. <strong>' . (int) $r['transferencia_id'] . '</strong> realizada: '
                   . '<strong>' . e(dinero($r['monto'])) . '</strong> a <strong>' . e($r['alias'])
                   . '</strong> (cta. ' . e($r['cuenta_destino']) . '). Saldo disponible: <strong>'
                   . e(dinero($r['saldo_disponible'])) . '</strong>.';
            $sel = 0;
            $montoTxt = '';
        } catch (Throwable $e) {
            $errores[] = mensaje_error($e);
        }
    }
}

$terceros = [];
$resumen = null;
try {
    $terceros = sp('sp_tercero_listar', [$auth['id']], 'i');
    $resumen = sp_uno('sp_usuario_resumen', [$auth['id']], 'i');
} catch (Throwable $e) {
    $errores[] = mensaje_error($e);
}

encabezado('Transferencia a cuenta de tercero');
?>
<div class="titulo-pagina">
  <div>
    <p class="etiqueta">Banca en línea</p>
    <h1>Transferencia a cuenta de tercero</h1>
  </div>
  <?php if ($resumen): ?>
    <p class="saldo-chico">Saldo disponible <strong><?= e(dinero($resumen['saldo'])) ?></strong></p>
  <?php endif; ?>
</div>

<section class="tarjeta tarjeta--formulario">
  <?php alertas($errores, $exito); ?>
  <?php if (!$terceros): ?>
    <p class="vacio">Para transferir primero debe <a href="<?= e(url('usuario/terceros.php')) ?>">agregar una cuenta de tercero</a>.</p>
  <?php else: ?>
  <form method="post" class="formulario" autocomplete="off">
    <?= csrf_campo() ?>
    <fieldset class="opciones">
      <legend>Seleccione la cuenta destino</legend>
      <?php foreach ($terceros as $t):
        $agotado = (int) $t['transacciones_hoy'] >= (int) $t['max_transacciones_diarias']; ?>
        <label class="opcion<?= $agotado ? ' opcion--agotada' : '' ?>">
          <input type="radio" name="tercero" value="<?= (int) $t['id'] ?>" required
                 <?= $sel === (int) $t['id'] ? 'checked' : '' ?> data-max="<?= e($t['monto_maximo']) ?>">
          <span class="opcion__cuerpo">
            <strong><?= e($t['alias']) ?></strong>
            <small>Cta. <?= e($t['numero_cuenta']) ?> · <?= e($t['titular']) ?></small>
            <small>Máximo <?= e(dinero($t['monto_maximo'])) ?> por transferencia ·
              <?= (int) $t['transacciones_hoy'] ?> de <?= (int) $t['max_transacciones_diarias'] ?> hoy<?= $agotado ? ' (límite alcanzado)' : '' ?></small>
          </span>
        </label>
      <?php endforeach; ?>
    </fieldset>
    <label for="monto">Cantidad a transferir (Q)</label>
    <input id="monto" name="monto" type="number" min="0.01" step="0.01" max="999999999999.99" required
           value="<?= e($montoTxt) ?>" placeholder="0.00">
    <button class="boton boton--bloque" type="submit">Transferir</button>
  </form>
  <?php endif; ?>
</section>
<?php
pie();
