<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$auth = requiere_rol('usuario');

$r = null;
$movs = [];
try {
    $r = sp_uno('sp_usuario_resumen', [$auth['id']], 'i');
    $movs = array_slice(array_reverse(sp('sp_estado_cuenta', [$auth['id'], null, null], 'iss')), 0, 5);
} catch (Throwable $e) {
    flash('error', mensaje_error($e));
}

encabezado('Mi cuenta');
?>
<div class="titulo-pagina">
  <div>
    <p class="etiqueta">Banca en línea</p>
    <h1>Hola, <?= e($auth['nombre']) ?></h1>
  </div>
  <p class="sutil"><?= e($auth['correo']) ?></p>
</div>

<?php if ($r): ?>
<section class="saldo">
  <div>
    <span>Cuenta monetaria</span>
    <strong class="saldo__cuenta"><?= e($r['numero_cuenta']) ?></strong>
  </div>
  <div class="saldo__monto">
    <span>Saldo disponible</span>
    <strong><?= e(dinero($r['saldo'])) ?></strong>
  </div>
  <div class="saldo__extra">
    <span><?= (int) $r['terceros'] ?> cuenta(s) de terceros</span>
    <span><?= (int) $r['transferencias_hoy'] ?> transferencia(s) hoy</span>
  </div>
</section>
<?php endif; ?>

<div class="rejilla rejilla--3">
  <a class="acceso" href="<?= e(url('usuario/terceros.php')) ?>">
    <span class="acceso__icono acceso__icono--usuario" aria-hidden="true"></span>
    <strong>Cuentas de terceros</strong>
    <span>Agregue cuentas destino con sus límites.</span>
  </a>
  <a class="acceso" href="<?= e(url('usuario/transferir.php')) ?>">
    <span class="acceso__icono acceso__icono--transferir" aria-hidden="true"></span>
    <strong>Transferir</strong>
    <span>Envíe dinero a sus cuentas de terceros.</span>
  </a>
  <a class="acceso" href="<?= e(url('usuario/estado-cuenta.php')) ?>">
    <span class="acceso__icono acceso__icono--log" aria-hidden="true"></span>
    <strong>Estado de cuenta</strong>
    <span>Todos sus débitos y créditos.</span>
  </a>
</div>

<section class="tarjeta">
  <h2>Últimos movimientos</h2>
  <?php if (!$movs): ?>
    <p class="vacio">Su cuenta aún no tiene movimientos.</p>
  <?php else: ?>
  <ul class="movimientos">
    <?php foreach ($movs as $m): $cred = $m['naturaleza'] === 'CREDITO'; ?>
      <li>
        <div><strong><?= e($m['descripcion']) ?></strong><small><?= e(fecha_hora($m['creado_en'])) ?></small></div>
        <span class="monto monto--<?= $cred ? 'credito' : 'debito' ?>"><?= $cred ? '+' : '−' ?> <?= e(dinero($m['credito'] ?? $m['debito'])) ?></span>
      </li>
    <?php endforeach; ?>
  </ul>
  <p><a href="<?= e(url('usuario/estado-cuenta.php')) ?>">Ver estado de cuenta completo &rarr;</a></p>
  <?php endif; ?>
</section>
<?php
pie();
