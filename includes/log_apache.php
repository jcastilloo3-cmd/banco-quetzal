<?php
function analizar_log_apache(string $ruta, bool $soloPaginas = false, string $prefijo = '', int $maxBytes = 8000000): array
{
    $r = [
        'existe' => is_file($ruta) && is_readable($ruta),
        'ruta' => $ruta, 'tamano' => 0, 'lineas' => 0, 'total' => 0,
        'clases' => ['2xx' => 0, '3xx' => 0, '4xx' => 0, '5xx' => 0],
        'codigos' => [], 'paginas' => [], 'errores' => [], 'ips' => [], 'metodos' => [],
        'por_hora' => array_fill(0, 24, 0), 'desde' => null, 'hasta' => null, 'bytes' => 0,
    ];
    if (!$r['existe']) {
        return $r;
    }
    $r['tamano'] = filesize($ruta);
    $fh = fopen($ruta, 'rb');
    if ($r['tamano'] > $maxBytes) {
        fseek($fh, -$maxBytes, SEEK_END);
        fgets($fh);
    }

    $re = '/^(\S+) \S+ \S+ \[([^\]]+)\] "(\S+) (\S+)[^"]*" (\d{3}) (\S+)/';
    while (($linea = fgets($fh)) !== false) {
        $r['lineas']++;
        if (!preg_match($re, $linea, $m)) {
            continue;
        }
        [, $ip, $fecha, $metodo, $ruta_req, $codigo, $bytes] = $m;
        $path = strtok($ruta_req, '?') ?: '/';

        if ($prefijo !== '' && strpos($path, $prefijo) !== 0) {
            continue;
        }
        if ($soloPaginas && preg_match('/\.(css|js|svg|png|jpe?g|gif|ico|webp|woff2?|map)$/i', $path)) {
            continue;
        }
        $r['total']++;
        $codigo = (int) $codigo;
        $clase = intdiv($codigo, 100) . 'xx';
        if (isset($r['clases'][$clase])) {
            $r['clases'][$clase]++;
        }
        $r['codigos'][$codigo] = ($r['codigos'][$codigo] ?? 0) + 1;
        $r['paginas'][$path] = ($r['paginas'][$path] ?? 0) + 1;
        $r['ips'][$ip] = true;
        $r['metodos'][$metodo] = ($r['metodos'][$metodo] ?? 0) + 1;
        $r['bytes'] += is_numeric($bytes) ? (int) $bytes : 0;
        if ($codigo >= 400) {
            $k = $codigo . ' ' . $path;
            $r['errores'][$k] = ($r['errores'][$k] ?? 0) + 1;
        }
        $t = DateTime::createFromFormat('d/M/Y:H:i:s O', $fecha);
        if ($t) {
            $r['por_hora'][(int) $t->format('G')]++;
            if ($r['desde'] === null || $t < $r['desde']) {
                $r['desde'] = $t;
            }
            if ($r['hasta'] === null || $t > $r['hasta']) {
                $r['hasta'] = $t;
            }
        }
    }
    fclose($fh);

    arsort($r['paginas']);
    arsort($r['errores']);
    ksort($r['codigos']);
    $r['paginas'] = array_slice($r['paginas'], 0, 10, true);
    $r['errores'] = array_slice($r['errores'], 0, 10, true);
    $r['visitantes'] = count($r['ips']);
    unset($r['ips']);
    return $r;
}

function goaccess_disponible(): bool
{
    if (!function_exists('shell_exec') || stripos((string) ini_get('disable_functions'), 'shell_exec') !== false) {
        return false;
    }
    $cmd = DIRECTORY_SEPARATOR === '\\' ? 'where ' : 'command -v ';
    $out = @shell_exec($cmd . escapeshellarg(GOACCESS_BIN) . ' 2>&1');
    return is_string($out) && trim($out) !== '' && stripos($out, 'no se pudo') === false
        && stripos($out, 'not find') === false && stripos($out, 'not found') === false;
}

function generar_reporte_goaccess(): array
{
    if (!is_file(APACHE_ACCESS_LOG)) {
        return [false, 'No se encontró el archivo de log: ' . APACHE_ACCESS_LOG];
    }
    $dir = dirname(GOACCESS_REPORTE);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $cmd = escapeshellarg(GOACCESS_BIN) . ' ' . escapeshellarg(APACHE_ACCESS_LOG)
         . ' --log-format=COMBINED --no-global-config --html-report-title=' . escapeshellarg('Banco Quetzal - Log de Apache')
         . ' -o ' . escapeshellarg(GOACCESS_REPORTE) . ' 2>&1';
    $salida = (string) @shell_exec($cmd);
    return is_file(GOACCESS_REPORTE) && filemtime(GOACCESS_REPORTE) >= time() - 60
        ? [true, 'Reporte de GoAccess generado correctamente.']
        : [false, 'GoAccess no pudo generar el reporte: ' . trim($salida)];
}
