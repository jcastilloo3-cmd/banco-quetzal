<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$auth = requiere_rol('cajero');

$errores = [];
$exito = '';
$d = ['nombre' => '', 'numero' => '', 'dpi' => '', 'monto' => ''];

if (es_post()) {
    $d['nombre'] = preg_replace('/\s+/', ' ', campo('nombre'));
    $d['numero'] = campo('numero');
    $d['dpi']    = campo('dpi');
    $d['monto']  = campo('monto');
    $monto = monto_valido($d['monto'], true);

    if (!csrf_valido()) {
        $errores[] = 'La sesión del formulario expiró. Recargue la página e intente de nuevo.';
    }
    if (!valida_nombre($d['nombre'])) {
        $errores[] = 'Ingrese el nombre de la cuenta (solo letras, de 3 a 100 caracteres).';
    }
    if (!valida_numero_cuenta($d['numero'])) {
        $errores[] = 'El número de cuenta debe tener entre 6 y 20 dígitos.';
    }
    if (!valida_dpi($d['dpi'])) {
        $errores[] = 'El DPI debe tener exactamente 13 dígitos.';
    }
    if ($monto === null) {
        $errores[] = 'El monto inicial debe ser un número mayor o igual a cero con máximo 2 decimales.';
    }

    if (!$errores) {
        try {
            $c = sp_uno('sp_cuenta_crear', [$d['numero'], $d['nombre'], $d['dpi'], $monto, $auth['id']], 'ssssi');
            $exito = 'Cuenta <strong>' . e($c['numero_cuenta']) . '</strong> creada para <strong>' . e($c['nombre'])
                   . '</strong> con saldo inicial de <strong>' . e(dinero($c['saldo'])) . '</strong>. '
                   . 'El cliente ya puede registrar su usuario en el portal.';
            $d = ['nombre' => '', 'numero' => '', 'dpi' => '', 'monto' => ''];
        } catch (Throwable $e) {
            $errores[] = mensaje_error($e);
        }
    }
}

encabezado('Crear cuenta monetaria');
?>
<div class="titulo-pagina">
  <div>
    <p class="etiqueta">Panel de cajero</p>
    <h1>Crear cuenta monetaria</h1>
  </div>
</div>

<section class="tarjeta tarjeta--formulario">
  <?php alertas($errores, $exito); ?>
  <form method="post" class="formulario formulario--2col" autocomplete="off">
    <?= csrf_campo() ?>
    <div class="col-completa">
      <label for="nombre">Nombre de la cuenta</label>
      <input id="nombre" name="nombre" maxlength="100" required value="<?= e($d['nombre']) ?>"
             placeholder="Nombre del titular">
    </div>
    <div>
      <label for="numero">No. de cuenta</label>
      <input id="numero" name="numero" inputmode="numeric" pattern="\d{6,20}" maxlength="20" required
             value="<?= e($d['numero']) ?>" title="Solo dígitos, de 6 a 20">
    </div>
    <div>
      <label for="dpi">Identificación (DPI)</label>
      <input id="dpi" name="dpi" inputmode="numeric" pattern="\d{13}" maxlength="13" required
             value="<?= e($d['dpi']) ?>" title="13 dígitos sin espacios">
    </div>
    <div>
      <label for="monto">Monto inicial (Q)</label>
      <input id="monto" name="monto" type="number" min="0" step="0.01" max="999999999999.99" required
             value="<?= e($d['monto']) ?>" placeholder="0.00">
    </div>
    <div class="col-completa">
      <button class="boton boton--bloque" type="submit">Crear cuenta</button>
    </div>
  </form>
</section>
<?php
pie();
