<?php
/**
 * Formulario compartido de depósito y retiro en ventanilla.
 */
function pagina_operacion_caja(string $tipo): void
{
    $auth = requiere_rol('cajero');
    $esDeposito = $tipo === 'deposito';
    $titulo = $esDeposito ? 'Depósito monetario' : 'Retiro monetario';
    $verbo = $esDeposito ? 'depositar' : 'debitar';

    $errores = [];
    $exito = '';
    $d = ['numero' => '', 'monto' => ''];

    if (es_post()) {
        $d['numero'] = campo('numero');
        $d['monto']  = campo('monto');
        $monto = monto_valido($d['monto']);

        if (!csrf_valido()) {
            $errores[] = 'La sesión del formulario expiró. Recargue la página e intente de nuevo.';
        }
        if (!valida_numero_cuenta($d['numero'])) {
            $errores[] = 'El número de cuenta debe tener entre 6 y 20 dígitos.';
        }
        if ($monto === null) {
            $errores[] = "La cantidad a $verbo debe ser mayor que cero, con máximo 2 decimales.";
        }
        if (!$errores) {
            try {
                $r = sp_uno($esDeposito ? 'sp_deposito' : 'sp_retiro', [$d['numero'], $monto, $auth['id']], 'ssi');
                $exito = ($esDeposito ? 'Depósito' : 'Retiro') . ' de <strong>' . e(dinero($monto)) . '</strong> '
                       . ($esDeposito ? 'acreditado a' : 'debitado de') . ' la cuenta <strong>' . e($r['numero_cuenta'])
                       . '</strong> (' . e($r['nombre']) . '). Nuevo saldo: <strong>' . e(dinero($r['saldo'])) . '</strong>.';
                $d = ['numero' => '', 'monto' => ''];
            } catch (Throwable $e) {
                $errores[] = mensaje_error($e);
            }
        }
    }

    encabezado($titulo);
    ?>
    <div class="titulo-pagina">
      <div>
        <p class="etiqueta">Panel de cajero</p>
        <h1><?= e($titulo) ?></h1>
      </div>
    </div>
    <section class="tarjeta tarjeta--formulario">
      <?php alertas($errores, $exito); ?>
      <form method="post" class="formulario" autocomplete="off">
        <?= csrf_campo() ?>
        <label for="numero">Número de cuenta</label>
        <input id="numero" name="numero" inputmode="numeric" pattern="\d{6,20}" maxlength="20" required
               value="<?= e($d['numero']) ?>" title="Solo dígitos, de 6 a 20">
        <label for="monto">Cantidad a <?= e($verbo) ?> (Q)</label>
        <input id="monto" name="monto" type="number" min="0.01" step="0.01" max="999999999999.99" required
               value="<?= e($d['monto']) ?>" placeholder="0.00">
        <button class="boton boton--bloque <?= $esDeposito ? '' : 'boton--peligro' ?>" type="submit">
          <?= $esDeposito ? 'Realizar depósito' : 'Realizar retiro' ?>
        </button>
      </form>
    </section>
    <?php
    pie();
}
