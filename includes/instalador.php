<?php
/**
 * Lógica común del instalador de la base de datos (CLI y página local).
 * Devuelve [ok, mensaje].
 */
function instalar_bd(bool $forzar = false): array
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $cn = mysqli_init();
    $flags = 0;
    if (DB_SSL) {
        $cn->ssl_set(null, null, DB_SSL_CA !== '' && is_file(DB_SSL_CA) ? DB_SSL_CA : null, null, null);
        $flags = MYSQLI_CLIENT_SSL | (DB_SSL_CA === '' ? MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT : 0);
    }
    $cn->real_connect(DB_HOST, DB_USER, DB_PASS, null, DB_PORT, null, $flags);
    $cn->set_charset('utf8mb4');
    $cn->query("CREATE DATABASE IF NOT EXISTS `" . $cn->real_escape_string(DB_NAME) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $cn->select_db(DB_NAME);

    $existe = $cn->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'administradores'")->fetch_row()[0];
    if ($existe && !$forzar) {
        return [true, 'La base de datos ya está instalada. Nada que hacer.'];
    }

    // Separar sentencias respetando DELIMITER
    $sql = file_get_contents(__DIR__ . '/../database/banco_quetzal.sql');
    $sentencias = [];
    $delim = ';';
    $actual = '';
    foreach (preg_split('/\R/', $sql) as $linea) {
        $t = trim($linea);
        if (preg_match('/^DELIMITER\s+(\S+)$/i', $t, $m)) {
            $delim = $m[1];
            continue;
        }
        if ($actual === '' && ($t === '' || str_starts_with($t, '--'))) {
            continue;
        }
        $actual .= $linea . "\n";
        if (str_ends_with(rtrim($linea), $delim)) {
            $sentencias[] = substr(rtrim($actual), 0, -strlen($delim));
            $actual = '';
        }
    }

    $n = 0;
    foreach ($sentencias as $s) {
        // La BD de destino la define DB_NAME (en Aiven se puede usar "defaultdb")
        if (preg_match('/^\s*(CREATE DATABASE|USE)\b/i', $s)) {
            continue;
        }
        $cn->query($s);
        $n++;
    }
    return [true, "Base de datos instalada: {$n} sentencias ejecutadas en '" . DB_NAME . "'."];
}
