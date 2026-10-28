<?php
require_once __DIR__ . '/includes/bootstrap.php';

encabezado('Inicio', ['rol' => null, 'clase' => 'inicio']);
?>
<section class="portada">
  <div class="portada__texto">
    <p class="etiqueta">Banca en línea</p>
    <h1>Su dinero, seguro y a un clic.</h1>
    <p>Consulte su estado de cuenta, registre cuentas de terceros y transfiera
       con límites que usted mismo define, desde la computadora o el celular.</p>
    <div class="portada__acciones">
      <a class="boton" href="<?= e(url('usuario/login.php')) ?>">Ingresar a Banca en línea</a>
      <a class="boton boton--borde" href="<?= e(url('registro.php')) ?>">Crear mi usuario</a>
    </div>
  </div>
  <div class="portada__figura" aria-hidden="true">
    <img src="<?= e(url('assets/img/logo.svg')) ?>" alt="" width="220" height="220">
  </div>
</section>

<section aria-labelledby="accesos-titulo">
  <h2 id="accesos-titulo" class="titulo-seccion">¿Cómo desea ingresar?</h2>
  <div class="rejilla rejilla--4">
    <a class="acceso" href="<?= e(url('admin/login.php')) ?>">
      <span class="acceso__icono acceso__icono--admin" aria-hidden="true"></span>
      <strong>Inicio de sesión de administrador</strong>
      <span>Gestión de cajeros y monitor de transferencias.</span>
    </a>
    <a class="acceso" href="<?= e(url('usuario/login.php')) ?>">
      <span class="acceso__icono acceso__icono--usuario" aria-hidden="true"></span>
      <strong>Inicio de sesión de usuario</strong>
      <span>Cuentas de terceros, transferencias y estado de cuenta.</span>
    </a>
    <a class="acceso" href="<?= e(url('cajero/login.php')) ?>">
      <span class="acceso__icono acceso__icono--cajero" aria-hidden="true"></span>
      <strong>Iniciar sesión de cajero</strong>
      <span>Apertura de cuentas, depósitos y retiros.</span>
    </a>
    <a class="acceso" href="<?= e(url('registro.php')) ?>">
      <span class="acceso__icono acceso__icono--registro" aria-hidden="true"></span>
      <strong>Registro de nuevo usuario</strong>
      <span>Cree su usuario con su número de cuenta y DPI.</span>
    </a>
  </div>
</section>

<section class="pasos" aria-labelledby="pasos-titulo">
  <h2 id="pasos-titulo" class="titulo-seccion">Empiece en tres pasos</h2>
  <ol class="rejilla rejilla--3">
    <li><strong>Abra su cuenta</strong><span>Visite una agencia: el cajero crea su cuenta monetaria con su DPI.</span></li>
    <li><strong>Cree su usuario</strong><span>Regístrese con su número de cuenta, correo y DPI.</span></li>
    <li><strong>Opere en línea</strong><span>Agregue cuentas de terceros y transfiera de forma segura.</span></li>
  </ol>
</section>
<?php
pie();
