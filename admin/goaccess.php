<?php
define('CSP_PERSONALIZADA', true);
require_once __DIR__ . '/../includes/bootstrap.php';
requiere_rol('admin');

if (!is_file(GOACCESS_REPORTE)) {
    flash('aviso', 'Aún no se ha generado el reporte de GoAccess.');
    redirigir('admin/logs.php');
}

header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; "
     . "style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; frame-ancestors 'none'");
header('Content-Type: text/html; charset=utf-8');
readfile(GOACCESS_REPORTE);
