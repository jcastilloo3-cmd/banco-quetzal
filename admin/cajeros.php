<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$auth = requiere_rol('admin');

$errores = [];
$d = ['nombre' => '', 'usuario' => ''];

if (es_post()) {
    if (!csrf_valido()) {
        $errores[] = 'La sesión del formulario expiró. Recargue la página e intente de nuevo.';
    } elseif (($_POST['accion'] ?? '') === 'estado') {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $bloquear = ($_POST['bloquear'] ?? '') === '1' ? 1 : 0;
        if ($id) {
            try {
                $c = sp_uno('sp_cajero_cambiar_estado', [$id, $bloquear], 'ii');
                flash('exito', 'El cajero "' . $c['usuario'] . '" fue ' . ($bloquear ? 'bloqueado.' : 'desbloqueado.'));
            } catch (Throwable $e) {
                flash('error', mensaje_error($e));
            }
        }
        redirigir('admin/cajeros.php');
    } else {
        $d['nombre']  = preg_replace('/\s+/', ' ', campo('nombre'));
        $d['usuario'] = campo('usuario');
        $clave    = (string) ($_POST['clave'] ?? '');
        $confirma = (string) ($_POST['confirmacion'] ?? '');

        if (!valida_nombre($d['nombre'])) {
            $errores[] = 'Ingrese el nombre completo (solo letras, de 3 a 100 caracteres).';
        }
        if (!valida_usuario($d['usuario'])) {
            $errores[] = 'El usuario debe tener de 4 a 30 caracteres: letras, números, punto, guion o guion bajo.';
        }
        if (!valida_clave($clave)) {
            $errores[] = MSJ_CLAVE;
        }
        if ($clave !== $confirma) {
            $errores[] = 'La clave y su confirmación no coinciden.';
        }
        if (!$errores) {
            try {
                sp('sp_cajero_crear', [$d['nombre'], $d['usuario'], password_hash($clave, PASSWORD_DEFAULT), $auth['id']], 'sssi');
                flash('exito', 'Cajero "' . $d['usuario'] . '" agregado correctamente.');
                redirigir('admin/cajeros.php');
            } catch (Throwable $e) {
                $errores[] = mensaje_error($e);
            }
        }
    }
}

$cajeros = [];
try {
    $cajeros = sp('sp_cajero_listar');
} catch (Throwable $e) {
    $errores[] = mensaje_error($e);
}

encabezado('Gestión de cajeros');
?>
<div class="titulo-pagina">
  <div>
    <p class="etiqueta">Panel de administrador</p>
    <h1>Gestión de usuarios de cajeros</h1>
  </div>
</div>

<div class="dos-columnas">
  <section class="tarjeta">
    <h2>Cajeros registrados <span class="contador"><?= count($cajeros) ?></span></h2>
    <?php if (!$cajeros): ?>
      <p class="vacio">Aún no hay cajeros. Agregue el primero con el formulario.</p>
    <?php else: ?>
    <div class="tabla-contenedor">
      <table class="tabla tabla--responsiva">
        <thead>
          <tr><th>Nombre completo</th><th>Usuario</th><th>Estado</th><th>Operaciones</th><th>Acción</th></tr>
        </thead>
        <tbody>
        <?php foreach ($cajeros as $c): $bloq = (int) $c['bloqueado'] === 1; ?>
          <tr>
            <td data-titulo="Nombre"><?= e($c['nombre_completo']) ?></td>
            <td data-titulo="Usuario"><code><?= e($c['usuario']) ?></code></td>
            <td data-titulo="Estado">
              <span class="estado estado--<?= $bloq ? 'bloqueado' : 'activo' ?>"><?= $bloq ? 'Bloqueado' : 'Activo' ?></span>
            </td>
            <td data-titulo="Operaciones"><?= (int) $c['operaciones'] ?></td>
            <td data-titulo="Acción">
              <form method="post" class="en-linea">
                <?= csrf_campo() ?>
                <input type="hidden" name="accion" value="estado">
                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                <input type="hidden" name="bloquear" value="<?= $bloq ? '0' : '1' ?>">
                <button type="submit" class="boton boton--chico <?= $bloq ? '' : 'boton--peligro' ?>">
                  <?= $bloq ? 'Desbloquear' : 'Bloquear' ?>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>

  <section class="tarjeta">
    <h2>Agregar cajero</h2>
    <?php alertas($errores); ?>
    <form method="post" class="formulario" autocomplete="off">
      <?= csrf_campo() ?>
      <input type="hidden" name="accion" value="crear">
      <label for="nombre">Nombre completo</label>
      <input id="nombre" name="nombre" maxlength="100" required value="<?= e($d['nombre']) ?>">
      <label for="usuario">Usuario</label>
      <input id="usuario" name="usuario" maxlength="30" pattern="[A-Za-z0-9._\-]{4,30}" required
             value="<?= e($d['usuario']) ?>" title="4 a 30 caracteres: letras, números, punto, guion o guion bajo">
      <label for="clave">Clave</label>
      <input id="clave" name="clave" type="password" minlength="8" maxlength="72" required
             autocomplete="new-password" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}" title="<?= e(MSJ_CLAVE) ?>">
      <label for="confirmacion">Confirmación de clave</label>
      <input id="confirmacion" name="confirmacion" type="password" minlength="8" maxlength="72" required
             autocomplete="new-password" data-igual-a="clave">
      <p class="ayuda"><?= e(MSJ_CLAVE) ?></p>
      <button class="boton boton--bloque" type="submit">Agregar cajero</button>
    </form>
  </section>
</div>
<?php
pie();
