<?php
function menu_por_rol(?string $rol): array
{
    switch ($rol) {
        case 'admin':
            return [
                'admin/index.php'    => 'Panel',
                'admin/cajeros.php'  => 'Cajeros',
                'admin/monitor.php'  => 'Monitor',
                'admin/logs.php'     => 'Log de Apache',
            ];
        case 'cajero':
            return [
                'cajero/index.php'        => 'Panel',
                'cajero/crear-cuenta.php' => 'Crear cuenta',
                'cajero/deposito.php'     => 'Depósito',
                'cajero/retiro.php'       => 'Retiro',
            ];
        case 'usuario':
            return [
                'usuario/index.php'         => 'Mi cuenta',
                'usuario/terceros.php'      => 'Cuentas de terceros',
                'usuario/transferir.php'    => 'Transferir',
                'usuario/estado-cuenta.php' => 'Estado de cuenta',
            ];
        default:
            return [
                'index.php'          => 'Inicio',
                'admin/login.php'    => 'Administrador',
                'usuario/login.php'  => 'Clientes',
                'cajero/login.php'   => 'Cajeros',
                'registro.php'       => 'Registrarse',
            ];
    }
}

function encabezado(string $titulo, array $op = []): void
{
    $auth   = usuario_actual();
    $rol    = $op['rol'] ?? ($auth['rol'] ?? null);
    $menu   = menu_por_rol($rol);
    $actual = ltrim(substr($_SERVER['SCRIPT_NAME'] ?? '', strlen(base_url())), '/');
    $clase  = $op['clase'] ?? '';
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Banco Quetzal - Banca en línea">
  <meta name="theme-color" content="#0b5d4b">
  <title><?= e($titulo) ?> | <?= e(APP_NOMBRE) ?></title>
  <link rel="icon" type="image/svg+xml" href="<?= e(url('assets/img/logo.svg')) ?>">
  <link rel="stylesheet" href="<?= e(url('assets/css/estilos.css')) ?>">
  <script src="<?= e(url('assets/js/app.js')) ?>" defer></script>
</head>
<body class="<?= e($clase) ?>">
  <a class="saltar" href="#contenido">Saltar al contenido</a>
  <header class="barra">
    <div class="barra__interior">
      <a class="marca" href="<?= e(url($auth ? array_key_first($menu) : 'index.php')) ?>">
        <img src="<?= e(url('assets/img/logo.svg')) ?>" alt="" width="44" height="44">
        <span class="marca__texto">
          <strong>Banco Quetzal</strong>
          <small>Banca en línea</small>
        </span>
      </a>
      <button class="menu-boton" type="button" aria-expanded="false" aria-controls="menu-principal">
        <span class="menu-boton__icono" aria-hidden="true"></span>
        <span class="sr">Abrir menú</span>
      </button>
      <nav id="menu-principal" class="menu" aria-label="Menú principal">
        <ul>
          <?php foreach ($menu as $ruta => $texto): ?>
            <li><a href="<?= e(url($ruta)) ?>"<?= $actual === $ruta ? ' aria-current="page"' : '' ?>><?= e($texto) ?></a></li>
          <?php endforeach; ?>
          <?php if ($auth): ?>
            <li class="menu__sesion">
              <span class="chip chip--<?= e($auth['rol']) ?>"><?= e(ROLES[$auth['rol']]) ?></span>
              <a class="boton boton--claro boton--chico" href="<?= e(url('logout.php')) ?>">Salir</a>
            </li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>
  </header>
  <main id="contenido" class="contenido">
    <?php foreach (tomar_flash() as $f): ?>
      <div class="alerta alerta--<?= e($f['tipo']) ?>" role="status"><?= e($f['mensaje']) ?></div>
    <?php endforeach; ?>
<?php
}

function pie(): void
{
    ?>
  </main>
  <footer class="pie">
    <div class="pie__interior">
      <p><strong>Banco Quetzal</strong> · Proyecto Desarrollo Web · Universidad Mariano Gálvez de Guatemala</p>
      <p>Jorge Otoniel Castillo Ortega · 0900-13-719 · <?= date('Y') ?></p>
    </div>
  </footer>
</body>
</html>
<?php
}

function alertas(array $errores, string $exito = ''): void
{
    if ($errores) {
        echo '<div class="alerta alerta--error" role="alert"><ul>';
        foreach ($errores as $er) {
            echo '<li>' . e($er) . '</li>';
        }
        echo '</ul></div>';
    }
    if ($exito !== '') {
        echo '<div class="alerta alerta--exito" role="status">' . $exito . '</div>';
    }
}

function pagina_login(string $rol): void
{
    $titulos = [
        'admin'   => ['Acceso de administrador', 'Usuario', 'text', 'username'],
        'cajero'  => ['Acceso de cajero', 'Usuario', 'text', 'username'],
        'usuario' => ['Banca en línea - Clientes', 'Correo electrónico', 'email', 'email'],
    ];
    [$titulo, $etiqueta, $tipo, $auto] = $titulos[$rol];
    $destino = ['admin' => 'admin/index.php', 'cajero' => 'cajero/index.php', 'usuario' => 'usuario/index.php'][$rol];

    $auth = usuario_actual();
    if ($auth && $auth['rol'] === $rol) {
        redirigir($destino);
    }

    $errores = [];
    $usuario = '';

    if (es_post()) {
        $usuario = campo('usuario');
        $clave   = $_POST['clave'] ?? '';
        $espera  = login_en_espera($rol);

        if (!csrf_valido()) {
            $errores[] = 'La sesión del formulario expiró. Recargue la página e intente de nuevo.';
        } elseif ($espera > 0) {
            $errores[] = 'Demasiados intentos fallidos. Espere ' . ceil($espera / 60) . ' minuto(s) e intente de nuevo.';
        } elseif ($usuario === '' || !is_string($clave) || $clave === '') {
            $errores[] = 'Ingrese su ' . strtolower($etiqueta) . ' y contraseña.';
        } else {
            try {
                if ($rol === 'admin') {
                    $f = sp_uno('sp_admin_obtener', [$usuario]);
                    if ($f && password_verify($clave, $f['clave_hash'])) {
                        iniciar_sesion('admin', (int) $f['id'], $f['nombre']);
                        redirigir($destino);
                    }
                } elseif ($rol === 'cajero') {
                    $f = sp_uno('sp_cajero_obtener', [$usuario]);
                    if ($f && password_verify($clave, $f['clave_hash'])) {
                        if ((int) $f['bloqueado'] === 1) {
                            $errores[] = 'Su usuario está bloqueado. Comuníquese con el administrador.';
                        } else {
                            iniciar_sesion('cajero', (int) $f['id'], $f['nombre_completo']);
                            redirigir($destino);
                        }
                    }
                } else {
                    $f = sp_uno('sp_usuario_obtener', [mb_strtolower($usuario)]);
                    if ($f && password_verify($clave, $f['clave_hash'])) {
                        iniciar_sesion('usuario', (int) $f['id'], $f['nombre'], [
                            'correo' => $f['correo'], 'cuenta' => $f['numero_cuenta'],
                        ]);
                        sp('sp_usuario_registrar_acceso', [(int) $f['id']], 'i');
                        redirigir($destino);
                    }
                }
                if (!$errores) {
                    login_fallido($rol);
                    $errores[] = 'Usuario o contraseña incorrectos.';
                }
            } catch (Throwable $e) {
                $errores[] = mensaje_error($e);
            }
        }
    }

    encabezado($titulo, ['rol' => null, 'clase' => 'fondo-acceso']);
    ?>
    <section class="tarjeta tarjeta--acceso">
      <div class="tarjeta__icono tarjeta__icono--<?= e($rol) ?>" aria-hidden="true"></div>
      <h1><?= e($titulo) ?></h1>
      <p class="sutil">Ingrese sus credenciales para continuar.</p>
      <?php alertas($errores); ?>
      <form method="post" class="formulario">
        <?= csrf_campo() ?>
        <label for="usuario"><?= e($etiqueta) ?></label>
        <input id="usuario" name="usuario" type="<?= e($tipo) ?>" autocomplete="<?= e($auto) ?>"
               value="<?= e($usuario) ?>" required maxlength="120" autofocus>
        <label for="clave">Contraseña</label>
        <input id="clave" name="clave" type="password" autocomplete="current-password" required maxlength="72">
        <button class="boton boton--bloque" type="submit">Iniciar sesión</button>
      </form>
      <?php if ($rol === 'usuario'): ?>
        <p class="centro">¿Aún no tiene usuario? <a href="<?= e(url('registro.php')) ?>">Regístrese aquí</a></p>
      <?php endif; ?>
      <p class="centro"><a href="<?= e(url('index.php')) ?>">&larr; Volver al inicio</a></p>
    </section>
    <?php
    pie();
}
