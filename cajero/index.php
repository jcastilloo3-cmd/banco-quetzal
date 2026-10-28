<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$auth = requiere_rol('cajero');

$ops = [];
try {
    $ops = sp('sp_cajero_operaciones_recientes', [$auth['id']], 'i');
} catch (Throwable $e) {
    flash('error', mensaje_error($e));
}
$tipos = ['DEPOSITO' => 'Depósito', 'RETIRO' => 'Retiro'];

encabezado('Panel de cajero');
?>
<div class="titulo-pagina">
  <div>
    <p class="etiqueta">Panel de cajero</p>
    <h1>Hola, <?= e($auth['nombre']) ?></h1>
  </div>
  <p class="sutil"><?= e(date('d/m/Y')) ?></p>
</div>

<div class="rejilla rejilla--3">
  <a class="acceso" href="<?= e(url('cajero/crear-cuenta.php')) ?>">
    <span class="acceso__icono acceso__icono--registro" aria-hidden="true"></span>
    <strong>Crear cuenta monetaria</strong>
    <span>Apertura de cuenta con nombre, número, DPI y monto inicial.</span>
  </a>
  <a class="acceso" href="<?= e(url('cajero/deposito.php')) ?>">
    <span class="acceso__icono acceso__icono--deposito" aria-hidden="true"></span>
    <strong>Depósito monetario</strong>
    <span>Acreditar fondos a una cuenta.</span>
  </a>
  <a class="acceso" href="<?= e(url('cajero/retiro.php')) ?>">
    <span class="acceso__icono acceso__icono--retiro" aria-hidden="true"></span>
    <strong>Retiro monetario</strong>
    <span>Debitar fondos de una cuenta.</span>
  </a>
</div>

<section class="tarjeta">
  <h2>Mis últimas operaciones</h2>
  <?php if (!$ops): ?>
    <p class="vacio">Todavía no ha registrado operaciones.</p>
  <?php else: ?>
  <div class="tabla-contenedor">
    <table class="tabla tabla--responsiva">
      <thead><tr><th>Fecha</th><th>Operación</th><th>Cuenta</th><th>Titular</th><th class="num">Monto</th></tr></thead>
      <tbody>
      <?php foreach ($ops as $o): ?>
        <tr>
          <td data-titulo="Fecha"><?= e(fecha_hora($o['creado_en'])) ?></td>
          <td data-titulo="Operación"><span class="estado estado--<?= $o['tipo'] === 'RETIRO' ? 'aviso' : 'activo' ?>"><?= e($tipos[$o['tipo']] ?? $o['tipo']) ?></span></td>
          <td data-titulo="Cuenta"><?= e($o['numero_cuenta']) ?></td>
          <td data-titulo="Titular"><?= e($o['nombre']) ?></td>
          <td data-titulo="Monto" class="num"><?= e(dinero($o['monto'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
<?php
pie();
