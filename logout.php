<?php
require_once __DIR__ . '/includes/bootstrap.php';

cerrar_sesion();
session_start();
flash('exito', 'Sesión cerrada correctamente. ¡Gracias por usar Banca en línea!');
redirigir('index.php');
