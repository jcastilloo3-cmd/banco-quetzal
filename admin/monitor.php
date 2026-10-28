<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/graficas.php';
requiere_rol('admin');

$fecha = $_GET['fecha'] ?? date('Y-m-d');
$f = DateTime::createFromFormat('Y-m-d', is_string($fecha) ? $fecha : '');
if (!$f || $f->format('Y-m-d') !== $fecha) {
    $fecha = date('Y-m-d');
}

$r = null;
$serie = [];
try {
    $r = sp_uno('sp_monitor_resumen', [$fecha]);
    // Últimos 7 días (se rellenan con cero los días sin movimientos)
    for ($i = 6; $i >= 0; $i--) {
        $dia = date('Y-m-d', strtotime("$fecha -$i day"));
        $serie[$dia] = ['dep' => 0, 'ret' => 0];
    }
    foreach (sp('sp_monitor_serie', [$fecha, 7], 'si') as $fila) {
        $serie[$fila['fecha']] = ['dep' => $fila['monto_depositos'], 'ret' => $fila['monto_retiros']];
    }
} catch (Throwable $e) {
    flash('error', mensaje_error($e));
}

$esHoy = $fecha === date('Y-m-d');
encabezado('Monitor de transferencias');
?>
<div class="titulo-pagina">
  <div>
    <p class="etiqueta">Panel de administrador</p>
    <h1>Monitor de transferencias</h1>
  </div>
  <form method="get" class="filtro">
    <label for="fecha">Fecha</label>
    <input id="fecha" name="fecha" type="date" value="<?= e($fecha) ?>" max="<?= e(date('Y-m-d')) ?>">
    <button class="boton boton--chico" type="submit">Ver</button>
  </form>
</div>

<?php if ($r): ?>
<section aria-labelledby="sec1">
  <h2 id="sec1" class="titulo-seccion">Indicadores <?= $esHoy ? 'del día de hoy' : 'del ' . e(date('d/m/Y', strtotime($fecha))) ?></h2>
  <div class="rejilla rejilla--kpi kpis">
    <div class="kpi"><span>Cuentas creadas</span><strong><?= (int) $r['cuentas_creadas'] ?></strong></div>
    <div class="kpi"><span>Usuarios cliente registrados</span><strong><?= (int) $r['usuarios_registrados'] ?></strong><small>Excluye usuarios de cajero</small></div>
    <div class="kpi"><span>Transacciones</span><strong><?= (int) $r['transacciones'] ?></strong><small>Depósitos + retiros + transferencias</small></div>
    <div class="kpi kpi--deposito"><span>Depósitos</span><strong><?= (int) $r['depositos'] ?></strong><small><?= e(dinero($r['monto_depositos'])) ?></small></div>
    <div class="kpi kpi--retiro"><span>Retiros</span><strong><?= (int) $r['retiros'] ?></strong><small><?= e(dinero($r['monto_retiros'])) ?></small></div>
    <div class="kpi"><span>Transferencias en línea</span><strong><?= (int) $r['transferencias'] ?></strong><small><?= e(dinero($r['monto_transferencias'])) ?></small></div>
  </div>
</section>

<section aria-labelledby="sec2" class="tarjeta">
  <h2 id="sec2">Cantidad monetaria: depósitos vs retiros</h2>
  <div class="leyenda">
    <span class="leyenda__item leyenda__item--deposito">Depósitos</span>
    <span class="leyenda__item leyenda__item--retiro">Retiros</span>
  </div>
  <div class="graficas">
    <figure>
      <?= grafica_comparativa((float) $r['monto_depositos'], (float) $r['monto_retiros']) ?>
      <figcaption>Total del <?= e(date('d/m/Y', strtotime($fecha))) ?></figcaption>
    </figure>
    <figure>
      <?= grafica_serie($serie) ?>
      <figcaption>Últimos 7 días</figcaption>
    </figure>
  </div>
</section>
<?php endif; ?>
<?php
pie();
