<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$auth = requiere_rol('admin');

$hoy = null;
try {
    $hoy = sp_uno('sp_monitor_resumen', [date('Y-m-d')]);
} catch (Throwable $e) {
    flash('error', mensaje_error($e));
}

encabezado('Panel de administrador');
?>
<div class="titulo-pagina">
  <div>
    <p class="etiqueta">Panel de administrador</p>
    <h1>Bienvenido, <?= e($auth['nombre']) ?></h1>
  </div>
  <p class="sutil"><?= e(date('d/m/Y')) ?></p>
</div>

<?php if ($hoy): ?>
<div class="rejilla rejilla--3 kpis">
  <div class="kpi"><span>Cuentas creadas hoy</span><strong><?= (int) $hoy['cuentas_creadas'] ?></strong></div>
  <div class="kpi"><span>Usuarios registrados hoy</span><strong><?= (int) $hoy['usuarios_registrados'] ?></strong></div>
  <div class="kpi"><span>Transacciones hoy</span><strong><?= (int) $hoy['transacciones'] ?></strong></div>
</div>
<?php endif; ?>

<div class="rejilla rejilla--3">
  <a class="acceso" href="<?= e(url('admin/cajeros.php')) ?>">
    <span class="acceso__icono acceso__icono--cajero" aria-hidden="true"></span>
    <strong>Gestión de cajeros</strong>
    <span>Listar, agregar y bloquear/desbloquear usuarios de cajero.</span>
  </a>
  <a class="acceso" href="<?= e(url('admin/monitor.php')) ?>">
    <span class="acceso__icono acceso__icono--monitor" aria-hidden="true"></span>
    <strong>Monitor de transferencias</strong>
    <span>Indicadores del día y gráfica de depósitos vs retiros.</span>
  </a>
  <a class="acceso" href="<?= e(url('admin/logs.php')) ?>">
    <span class="acceso__icono acceso__icono--log" aria-hidden="true"></span>
    <strong>Log de Apache</strong>
    <span>Solicitudes OK, páginas más visitadas y páginas con error.</span>
  </a>
</div>
<?php
pie();
