<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/instalador.php';

for ($intento = 1; ; $intento++) {
    try {
        [$ok, $msj] = instalar_bd(in_array('--forzar', $argv, true));
        echo "[instalar] $msj\n";
        exit($ok ? 0 : 1);
    } catch (mysqli_sql_exception $e) {
        if ($intento >= 5) {
            fwrite(STDERR, "[instalar] Error: {$e->getMessage()}\n");
            exit(1);
        }
        sleep(3);
    }
}
