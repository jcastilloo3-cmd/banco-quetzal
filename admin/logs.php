<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/log_apache.php';
requiere_rol('admin');

$goaccess = goaccess_disponible();

if (es_post()) {
    if (!csrf_valido()) {
        flash('error', 'La sesión del formulario expiró. Intente de nuevo.');
    } elseif ($goaccess) {
        [$ok, $msj] = generar_reporte_goaccess();
        flash($ok ? 'exito' : 'error', $msj);
    }
    redirigir('admin/logs.php');
}

$soloPaginas = ($_GET['vista'] ?? 'paginas') !== 'todo';
$a = analizar_log_apache(APACHE_ACCESS_LOG, $soloPaginas, base_url() !== '' ? base_url() . '/' : '');
$hayReporte = is_file(GOACCESS_REPORTE);

function pct(int $n, int $t): string
{
    return $t > 0 ? number_format($n * 100 / $t, 1) . '%' : '0%';
}

encabezado('Log de Apache');
?>
<div class="titulo-pagina">
  <div>
    <p class="etiqueta">Administración, mantenimiento y soporte del servidor web</p>
    <h1>Análisis del log de Apache</h1>
  </div>
  <div class="segmentado" role="group" aria-label="Tipo de solicitudes">
    <a href="?vista=paginas"<?= $soloPaginas ? ' aria-current="true"' : '' ?>>Solo páginas</a>
    <a href="?vista=todo"<?= !$soloPaginas ? ' aria-current="true"' : '' ?>>Todo (incluye CSS/JS/imágenes)</a>
  </div>
</div>

<section class="tarjeta">
  <h2>GoAccess — reporte completo</h2>
  <p>El servidor usa <strong>GoAccess</strong> (analizador de logs de código abierto) para generar un reporte
     interactivo del archivo <code><?= e(APACHE_ACCESS_LOG) ?></code>.
     <?php if (base_url() !== ''): ?>El resumen considera solo las solicitudes a <code><?= e(base_url()) ?>/</code>.<?php endif; ?></p>
  <div class="acciones">
    <?php if ($goaccess): ?>
      <form method="post" class="en-linea">
        <?= csrf_campo() ?>
        <button class="boton" type="submit">Generar / actualizar reporte</button>
      </form>
    <?php endif; ?>
    <?php if ($hayReporte): ?>
      <a class="boton boton--borde" href="<?= e(url('admin/goaccess.php')) ?>" target="_blank" rel="noopener">
        Ver reporte GoAccess (<?= e(date('d/m/Y H:i', filemtime(GOACCESS_REPORTE))) ?>)
      </a>
    <?php endif; ?>
  </div>
  <?php if (!$goaccess): ?>
    <p class="ayuda">GoAccess no está instalado en este servidor (en WampServer/Windows se usa el resumen de abajo;
      en Render el contenedor Docker ya trae GoAccess instalado).</p>
  <?php endif; ?>
</section>

<?php if (!$a['existe']): ?>
  <div class="alerta alerta--aviso">No se encontró o no se puede leer el log en <code><?= e($a['ruta']) ?></code>.
    Configure la ruta con la variable <code>APACHE_ACCESS_LOG</code>.</div>
<?php else: ?>
<section aria-labelledby="res">
  <h2 id="res" class="titulo-seccion">Resumen
    <small class="sutil">
      <?= $a['desde'] ? e($a['desde']->format('d/m/Y H:i')) . ' a ' . e($a['hasta']->format('d/m/Y H:i')) : '' ?>
      · <?= e(number_format($a['tamano'] / 1024, 1)) ?> KB
    </small>
  </h2>
  <div class="rejilla rejilla--kpi kpis">
    <div class="kpi"><span>Solicitudes</span><strong><?= number_format($a['total']) ?></strong></div>
    <div class="kpi kpi--deposito"><span>OK (2xx)</span><strong><?= number_format($a['clases']['2xx']) ?></strong><small><?= pct($a['clases']['2xx'], $a['total']) ?></small></div>
    <div class="kpi"><span>Redirecciones (3xx)</span><strong><?= number_format($a['clases']['3xx']) ?></strong><small><?= pct($a['clases']['3xx'], $a['total']) ?></small></div>
    <div class="kpi kpi--retiro"><span>Errores cliente (4xx)</span><strong><?= number_format($a['clases']['4xx']) ?></strong><small><?= pct($a['clases']['4xx'], $a['total']) ?></small></div>
    <div class="kpi kpi--retiro"><span>Errores servidor (5xx)</span><strong><?= number_format($a['clases']['5xx']) ?></strong><small><?= pct($a['clases']['5xx'], $a['total']) ?></small></div>
    <div class="kpi"><span>Visitantes (IP únicas)</span><strong><?= number_format($a['visitantes']) ?></strong></div>
  </div>
</section>

<div class="dos-columnas">
  <section class="tarjeta">
    <h2>Páginas más visitadas</h2>
    <?php if (!$a['paginas']): ?><p class="vacio">Sin datos.</p><?php else: $max = max($a['paginas']); ?>
    <ol class="ranking">
      <?php foreach ($a['paginas'] as $p => $n): ?>
        <li>
          <span class="ranking__texto"><code><?= e($p) ?></code></span>
          <span class="ranking__barra"><meter min="0" max="<?= $max ?>" value="<?= $n ?>"></meter></span>
          <strong><?= number_format($n) ?></strong>
        </li>
      <?php endforeach; ?>
    </ol>
    <?php endif; ?>
  </section>

  <section class="tarjeta">
    <h2>Páginas con error (4xx / 5xx)</h2>
    <?php if (!$a['errores']): ?><p class="vacio">No hay solicitudes con error.</p><?php else: ?>
    <div class="tabla-contenedor">
      <table class="tabla">
        <thead><tr><th>Código</th><th>Página</th><th>Veces</th></tr></thead>
        <tbody>
        <?php foreach ($a['errores'] as $k => $n): [$cod, $pag] = explode(' ', $k, 2); ?>
          <tr><td><span class="estado estado--<?= $cod >= 500 ? 'bloqueado' : 'aviso' ?>"><?= e($cod) ?></span></td>
              <td><code><?= e($pag) ?></code></td><td><?= number_format($n) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>

  <section class="tarjeta">
    <h2>Códigos de estado HTTP</h2>
    <div class="tabla-contenedor">
      <table class="tabla">
        <thead><tr><th>Código</th><th>Solicitudes</th><th>%</th></tr></thead>
        <tbody>
        <?php foreach ($a['codigos'] as $c => $n): ?>
          <tr><td><?= e($c) ?></td><td><?= number_format($n) ?></td><td><?= pct($n, $a['total']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="tarjeta">
    <h2>Solicitudes por hora del día</h2>
    <?php $maxH = max(max($a['por_hora']), 1); ?>
    <div class="horas" role="img" aria-label="Distribución de solicitudes por hora">
      <?php foreach ($a['por_hora'] as $h => $n): ?>
        <div class="horas__col" title="<?= sprintf('%02d:00', $h) ?> — <?= $n ?> solicitudes">
          <meter class="horas__barra" min="0" max="<?= $maxH ?>" value="<?= $n ?>"></meter>
          <span><?= $h % 3 === 0 ? sprintf('%02d', $h) : '' ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</div>
<?php endif; ?>
<?php
pie();
