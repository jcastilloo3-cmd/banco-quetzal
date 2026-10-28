<?php
require_once __DIR__ . '/includes/bootstrap.php';

$errores = [];
$d = ['numero' => '', 'correo' => '', 'dpi' => ''];

if (es_post()) {
    $d['numero'] = campo('numero');
    $d['correo'] = mb_strtolower(campo('correo'));
    $d['dpi']    = campo('dpi');
    $clave       = (string) ($_POST['clave'] ?? '');
    $confirma    = (string) ($_POST['confirmacion'] ?? '');

    if (!csrf_valido()) {
        $errores[] = 'La sesión del formulario expiró. Recargue la página e intente de nuevo.';
    }
    if (!valida_numero_cuenta($d['numero'])) {
        $errores[] = 'El número de cuenta debe tener entre 6 y 20 dígitos.';
    }
    if (!valida_correo($d['correo'])) {
        $errores[] = 'Ingrese un correo electrónico válido.';
    }
    if (!valida_dpi($d['dpi'])) {
        $errores[] = 'El DPI debe tener exactamente 13 dígitos.';
    }
    if (!valida_clave($clave)) {
        $errores[] = MSJ_CLAVE;
    }
    if ($clave !== $confirma) {
        $errores[] = 'La contraseña y su confirmación no coinciden.';
    }

    if (!$errores) {
        try {
            // El SP valida: cuenta existente, DPI coincidente, cuenta sin usuario y correo único
            sp('sp_usuario_registrar', [
                $d['numero'], $d['correo'], $d['dpi'], password_hash($clave, PASSWORD_DEFAULT),
            ]);
            flash('exito', 'Usuario creado correctamente. Ya puede iniciar sesión con su correo.');
            redirigir('usuario/login.php');
        } catch (Throwable $e) {
            $errores[] = mensaje_error($e);
        }
    }
}

encabezado('Registro de nuevo usuario', ['rol' => null, 'clase' => 'fondo-acceso']);
?>
<section class="tarjeta tarjeta--acceso tarjeta--ancha">
  <div class="tarjeta__icono tarjeta__icono--registro" aria-hidden="true"></div>
  <h1>Registro de nuevo usuario</h1>
  <p class="sutil">Para crear su usuario, su cuenta bancaria debe haber sido abierta previamente
     en una agencia. Su correo electrónico será su nombre de usuario.</p>
  <?php alertas($errores); ?>
  <form method="post" class="formulario formulario--2col" autocomplete="off">
    <?= csrf_campo() ?>
    <div>
      <label for="numero">No. de cuenta bancaria</label>
      <input id="numero" name="numero" inputmode="numeric" pattern="\d{6,20}" maxlength="20" required
             value="<?= e($d['numero']) ?>" title="Solo dígitos, de 6 a 20">
    </div>
    <div>
      <label for="dpi">DPI</label>
      <input id="dpi" name="dpi" inputmode="numeric" pattern="\d{13}" maxlength="13" required
             value="<?= e($d['dpi']) ?>" title="13 dígitos sin espacios">
    </div>
    <div class="col-completa">
      <label for="correo">Correo electrónico <small>(será su usuario)</small></label>
      <input id="correo" name="correo" type="email" maxlength="120" required autocomplete="email"
             value="<?= e($d['correo']) ?>">
    </div>
    <div>
      <label for="clave">Contraseña</label>
      <input id="clave" name="clave" type="password" minlength="8" maxlength="72" required
             autocomplete="new-password" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}"
             title="<?= e(MSJ_CLAVE) ?>">
    </div>
    <div>
      <label for="confirmacion">Confirmación de contraseña</label>
      <input id="confirmacion" name="confirmacion" type="password" minlength="8" maxlength="72" required
             autocomplete="new-password" data-igual-a="clave">
    </div>
    <p class="ayuda col-completa"><?= e(MSJ_CLAVE) ?></p>
    <div class="col-completa">
      <button class="boton boton--bloque" type="submit">Crear usuario</button>
    </div>
  </form>
  <p class="centro">¿Ya tiene usuario? <a href="<?= e(url('usuario/login.php')) ?>">Inicie sesión</a></p>
</section>
<?php
pie();
