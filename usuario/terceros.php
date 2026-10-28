<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$auth = requiere_rol('usuario');

$errores = [];
$exito = '';
$d = ['numero' => '', 'monto' => '', 'max' => '', 'alias' => ''];

if (es_post()) {
    $d['numero'] = campo('numero');
    $d['monto']  = campo('monto');
    $d['max']    = campo('max');
    $d['alias']  = preg_replace('/\s+/', ' ', campo('alias'));
    $monto = monto_valido($d['monto']);
    $max = filter_var($d['max'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]);

    if (!csrf_valido()) {
        $errores[] = 'La sesión del formulario expiró. Recargue la página e intente de nuevo.';
    }
    if (!valida_numero_cuenta($d['numero'])) {
        $errores[] = 'El número de cuenta debe tener entre 6 y 20 dígitos.';
    }
    if ($monto === null) {
        $errores[] = 'El monto máximo debe ser mayor que cero, con máximo 2 decimales.';
    }
    if ($max === false) {
        $errores[] = 'La cantidad máxima de transacciones diarias debe ser un número entero entre 1 y 100.';
    }
    if (mb_strlen($d['alias']) < 2 || mb_strlen($d['alias']) > 60) {
        $errores[] = 'El alias debe tener entre 2 y 60 caracteres.';
    }

    if (!$errores) {
        try {
            // El SP valida que la cuenta exista, que no sea la propia y que no esté repetida
            sp('sp_tercero_agregar', [$auth['id'], $d['numero'], $monto, $max, $d['alias']], 'issis');
            $exito = 'Cuenta de tercero <strong>' . e($d['alias']) . '</strong> agregada correctamente.';
            $d = ['numero' => '', 'monto' => '', 'max' => '', 'alias' => ''];
        } catch (Throwable $e) {
            $errores[] = mensaje_error($e);
        }
    }
}

$terceros = [];
try {
    $terceros = sp('sp_tercero_listar', [$auth['id']], 'i');
} catch (Throwable $e) {
    $errores[] = mensaje_error($e);
}

encabezado('Cuentas de terceros');
?>
<div class="titulo-pagina">
  <div>
    <p class="etiqueta">Banca en línea</p>
    <h1>Cuentas de terceros</h1>
  </div>
</div>

<div class="dos-columnas dos-columnas--invertida">
  <section class="tarjeta">
    <h2>Agregar cuenta de tercero</h2>
    <?php alertas($errores, $exito); ?>
    <form method="post" class="formulario" autocomplete="off">
      <?= csrf_campo() ?>
      <label for="numero">No. de cuenta</label>
      <input id="numero" name="numero" inputmode="numeric" pattern="\d{6,20}" maxlength="20" required
             value="<?= e($d['numero']) ?>" title="Solo dígitos, de 6 a 20">
      <label for="monto">Monto máximo por transferencia (Q)</label>
      <input id="monto" name="monto" type="number" min="0.01" step="0.01" max="999999999999.99" required
             value="<?= e($d['monto']) ?>" placeholder="0.00">
      <label for="max">Cantidad máxima de transacciones diarias</label>
      <input id="max" name="max" type="number" min="1" max="100" step="1" required value="<?= e($d['max']) ?>">
      <label for="alias">Alias</label>
      <input id="alias" name="alias" minlength="2" maxlength="60" required value="<?= e($d['alias']) ?>"
             placeholder="Ej.: Mamá, Renta, Proveedor">
      <button class="boton boton--bloque" type="submit">Agregar cuenta</button>
    </form>
  </section>

  <section class="tarjeta">
    <h2>Mis cuentas de terceros <span class="contador"><?= count($terceros) ?></span></h2>
    <?php if (!$terceros): ?>
      <p class="vacio">Aún no tiene cuentas de terceros registradas.</p>
    <?php else: ?>
    <div class="tabla-contenedor">
      <table class="tabla tabla--responsiva">
        <thead><tr><th>Alias</th><th>Cuenta</th><th>Titular</th><th class="num">Monto máximo</th><th>Transf. hoy</th></tr></thead>
        <tbody>
        <?php foreach ($terceros as $t): ?>
          <tr>
            <td data-titulo="Alias"><strong><?= e($t['alias']) ?></strong></td>
            <td data-titulo="Cuenta"><?= e($t['numero_cuenta']) ?></td>
            <td data-titulo="Titular"><?= e($t['titular']) ?></td>
            <td data-titulo="Monto máximo" class="num"><?= e(dinero($t['monto_maximo'])) ?></td>
            <td data-titulo="Transf. hoy"><?= (int) $t['transacciones_hoy'] ?> / <?= (int) $t['max_transacciones_diarias'] ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p><a class="boton boton--borde" href="<?= e(url('usuario/transferir.php')) ?>">Transferir a un tercero</a></p>
    <?php endif; ?>
  </section>
</div>
<?php
pie();
