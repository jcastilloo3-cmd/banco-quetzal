<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$auth = requiere_rol('usuario');

function fecha_get(string $k): ?string
{
    $v = $_GET[$k] ?? '';
    if (!is_string($v) || $v === '') {
        return null;
    }
    $f = DateTime::createFromFormat('Y-m-d', $v);
    return $f && $f->format('Y-m-d') === $v ? $v : null;
}

$desde = fecha_get('desde');
$hasta = fecha_get('hasta');
if ($desde && $hasta && $desde > $hasta) {
    [$desde, $hasta] = [$hasta, $desde];
}

$movs = [];
$r = null;
try {
    $r = sp_uno('sp_usuario_resumen', [$auth['id']], 'i');
    $movs = sp('sp_estado_cuenta', [$auth['id'], $desde, $hasta], 'iss');
} catch (Throwable $e) {
    flash('error', mensaje_error($e));
}

$totDeb = $totCred = 0.0;
foreach ($movs as $m) {
    $totDeb  += (float) $m['debito'];
    $totCred += (float) $m['credito'];
}
$tipos = [
    'DEPOSITO' => 'Depósito', 'RETIRO' => 'Retiro',
    'TRANSFERENCIA_ENVIADA' => 'Transferencia enviada', 'TRANSFERENCIA_RECIBIDA' => 'Transferencia recibida',
];

encabezado('Estado de cuenta');
?>
<div class="titulo-pagina">
  <div>
    <p class="etiqueta">Banca en línea</p>
    <h1>Estado de cuenta</h1>
  </div>
  <button class="boton boton--borde boton--chico no-imprimir" type="button" data-imprimir>Imprimir</button>
</div>

<?php if ($r): ?>
<section class="tarjeta encabezado-estado">
  <dl>
    <div><dt>Titular</dt><dd><?= e($r['nombre']) ?></dd></div>
    <div><dt>Cuenta</dt><dd><?= e($r['numero_cuenta']) ?></dd></div>
    <div><dt>Período</dt><dd><?= $desde ? e(date('d/m/Y', strtotime($desde))) : 'Inicio' ?> – <?= $hasta ? e(date('d/m/Y', strtotime($hasta))) : 'Hoy' ?></dd></div>
    <div><dt>Saldo actual</dt><dd class="destacado"><?= e(dinero($r['saldo'])) ?></dd></div>
  </dl>
</section>
<?php endif; ?>

<form method="get" class="filtro filtro--fechas no-imprimir">
  <div><label for="desde">Desde</label><input id="desde" name="desde" type="date" value="<?= e($desde) ?>"></div>
  <div><label for="hasta">Hasta</label><input id="hasta" name="hasta" type="date" value="<?= e($hasta) ?>"></div>
  <button class="boton boton--chico" type="submit">Filtrar</button>
  <a class="boton boton--claro boton--chico" href="<?= e(url('usuario/estado-cuenta.php')) ?>">Todo</a>
</form>

<section class="tarjeta">
  <div class="totales">
    <span>Créditos <strong class="monto--credito"><?= e(dinero($totCred)) ?></strong></span>
    <span>Débitos <strong class="monto--debito"><?= e(dinero($totDeb)) ?></strong></span>
    <span>Movimientos <strong><?= count($movs) ?></strong></span>
  </div>
  <?php if (!$movs): ?>
    <p class="vacio">No hay movimientos en el período seleccionado.</p>
  <?php else: ?>
  <div class="tabla-contenedor">
    <table class="tabla tabla--responsiva tabla--estado">
      <thead>
        <tr><th>Fecha</th><th>Tipo</th><th>Descripción</th><th class="num">Débito</th><th class="num">Crédito</th><th class="num">Saldo</th></tr>
      </thead>
      <tbody>
      <?php foreach ($movs as $m): ?>
        <tr>
          <td data-titulo="Fecha"><?= e(fecha_hora($m['creado_en'])) ?></td>
          <td data-titulo="Tipo"><?= e($tipos[$m['tipo']] ?? $m['tipo']) ?></td>
          <td data-titulo="Descripción"><?= e($m['descripcion']) ?></td>
          <td data-titulo="Débito" class="num monto--debito"><?= $m['debito'] !== null ? e(dinero($m['debito'])) : '' ?></td>
          <td data-titulo="Crédito" class="num monto--credito"><?= $m['credito'] !== null ? e(dinero($m['credito'])) : '' ?></td>
          <td data-titulo="Saldo" class="num"><?= e(dinero($m['saldo_resultante'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr><th colspan="3">Totales</th><th class="num"><?= e(dinero($totDeb)) ?></th><th class="num"><?= e(dinero($totCred)) ?></th><th></th></tr>
      </tfoot>
    </table>
  </div>
  <?php endif; ?>
</section>
<?php
pie();
