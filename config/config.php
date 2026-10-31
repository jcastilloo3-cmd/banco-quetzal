<?php
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $h, string $n): bool { return $n === '' || strncmp($h, $n, strlen($n)) === 0; }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $h, string $n): bool { return $n === '' || substr($h, -strlen($n)) === $n; }
}

function env_o(string $clave, $defecto)
{
    $v = getenv($clave);
    return ($v === false || $v === '') ? $defecto : $v;
}

define('DB_HOST', env_o('DB_HOST', 'localhost'));
define('DB_PORT', (int) env_o('DB_PORT', 3306));
define('DB_NAME', env_o('DB_NAME', 'banco_quetzal'));
define('DB_USER', env_o('DB_USER', 'root'));
define('DB_PASS', env_o('DB_PASS', ''));
define('DB_SSL',  env_o('DB_SSL', '0') === '1');
define('DB_SSL_CA', env_o('DB_SSL_CA', ''));

define('APP_NOMBRE', 'Banco Quetzal');
define('APP_ZONA_HORARIA', 'America/Guatemala');
define('APP_MINUTOS_SESION', (int) env_o('APP_MINUTOS_SESION', 15));
define('APP_INTENTOS_LOGIN', 5);
define('APP_ESPERA_LOGIN', 300);

define('APP_BASE_URL', env_o('APP_BASE_URL', ''));

define('APACHE_ACCESS_LOG', env_o('APACHE_ACCESS_LOG',
    DIRECTORY_SEPARATOR === '\\'
        ? str_replace('\\', '/', dirname(__DIR__, 3)) . '/logs/access.log'
        : '/var/log/apache2/banco_access.log'));

define('GOACCESS_REPORTE', env_o('GOACCESS_REPORTE', __DIR__ . '/../storage/goaccess.html'));
define('GOACCESS_BIN', env_o('GOACCESS_BIN', 'goaccess'));
