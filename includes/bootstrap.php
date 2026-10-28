<?php
/**
 * Arranque común de todas las páginas: configuración, sesión segura,
 * conexión a MySQL (solo por procedimientos almacenados), CSRF y utilidades.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

date_default_timezone_set(APP_ZONA_HORARIA);

$debug = getenv('APP_DEBUG') === '1';
ini_set('display_errors', $debug ? '1' : '0');
error_reporting(E_ALL);

// ---------------------------------------------------------------------
// Cabeceras de seguridad
// ---------------------------------------------------------------------
if (!headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if (!defined('CSP_PERSONALIZADA')) {
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; "
             . "script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
    }
}

// ---------------------------------------------------------------------
// Sesión
// ---------------------------------------------------------------------
function es_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('BQSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => es_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// ---------------------------------------------------------------------
// URLs
// ---------------------------------------------------------------------
function base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    if (APP_BASE_URL !== '') {
        return $base = rtrim(APP_BASE_URL, '/');
    }
    $docRoot = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $appRoot = str_replace('\\', '/', (string) realpath(__DIR__ . '/..'));
    if ($docRoot !== '' && stripos($appRoot, $docRoot) === 0) {
        $base = substr($appRoot, strlen($docRoot));
    } else {
        $base = '';
    }
    return $base = rtrim($base, '/');
}

function url(string $ruta = ''): string
{
    return base_url() . '/' . ltrim($ruta, '/');
}

function redirigir(string $ruta): void
{
    header('Location: ' . url($ruta));
    exit;
}

function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function dinero($monto): string
{
    return 'Q ' . number_format((float) $monto, 2, '.', ',');
}

function fecha_hora(?string $valor): string
{
    if (!$valor) {
        return '—';
    }
    return date('d/m/Y H:i', strtotime($valor));
}

// ---------------------------------------------------------------------
// Base de datos: todo acceso se hace con CALL a procedimientos almacenados
// ---------------------------------------------------------------------
if (!function_exists('sp')) {
    function db(): mysqli
    {
        static $cn = null;
        if ($cn instanceof mysqli) {
            return $cn;
        }
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $cn = mysqli_init();
        $flags = 0;
        if (DB_SSL) {
            $ca = DB_SSL_CA !== '' && is_file(DB_SSL_CA) ? DB_SSL_CA : null;
            $cn->ssl_set(null, null, $ca, null, null);
            $flags = MYSQLI_CLIENT_SSL;
            if ($ca === null) {
                $flags |= MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
            }
        }
        $cn->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT, null, $flags);
        $cn->set_charset('utf8mb4');
        $cn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        // Guatemala no usa horario de verano: UTC-6 todo el año
        $cn->query("SET time_zone = '-06:00'");
        return $cn;
    }

    /**
     * Ejecuta un procedimiento almacenado con parámetros enlazados y devuelve
     * las filas del primer conjunto de resultados.
     *
     * @param string $sp     nombre del procedimiento (solo letras y _)
     * @param array  $params valores de los parámetros IN
     * @param string $tipos  tipos para bind_param (s, i, d); por defecto todos 's'
     */
    function sp(string $sp, array $params = [], string $tipos = ''): array
    {
        if (!preg_match('/^sp_[a-z_]+$/', $sp)) {
            throw new InvalidArgumentException('Nombre de procedimiento no válido');
        }
        $marcas = implode(',', array_fill(0, count($params), '?'));
        $stmt = db()->prepare("CALL {$sp}({$marcas})");
        if ($params) {
            if ($tipos === '') {
                $tipos = str_repeat('s', count($params));
            }
            $stmt->bind_param($tipos, ...$params);
        }
        $stmt->execute();

        $filas = [];
        $res = $stmt->get_result();
        if ($res instanceof mysqli_result) {
            $filas = $res->fetch_all(MYSQLI_ASSOC);
            $res->free();
        }
        // Consumir los conjuntos restantes que devuelve CALL
        while ($stmt->more_results() && $stmt->next_result()) {
            $r = $stmt->get_result();
            if ($r instanceof mysqli_result) {
                $r->free();
            }
        }
        $stmt->close();
        return $filas;
    }
}

function sp_uno(string $sp, array $params = [], string $tipos = ''): ?array
{
    $filas = sp($sp, $params, $tipos);
    return $filas[0] ?? null;
}

/** Mensaje para el usuario: reglas de negocio (SIGNAL 45000) o error genérico. */
function mensaje_error(Throwable $e): string
{
    // SIGNAL SQLSTATE '45000' llega con el código de error 1644 (ER_SIGNAL_EXCEPTION)
    if ($e instanceof mysqli_sql_exception && $e->getCode() === 1644) {
        return $e->getMessage();
    }
    error_log('[BancoQuetzal] ' . get_class($e) . ': ' . $e->getMessage());
    if ($e instanceof mysqli_sql_exception && in_array($e->getCode(), [2002, 1045, 1049, 2005], true)) {
        return 'No fue posible conectar con la base de datos. Intente más tarde.';
    }
    return 'Ocurrió un error al procesar la solicitud. Intente nuevamente.';
}

// ---------------------------------------------------------------------
// CSRF y mensajes flash
// ---------------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valido(): bool
{
    return isset($_POST['csrf']) && is_string($_POST['csrf'])
        && hash_equals(csrf_token(), $_POST['csrf']);
}

function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function tomar_flash(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function es_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function campo(string $nombre): string
{
    $v = $_POST[$nombre] ?? '';
    return is_string($v) ? trim($v) : '';
}

// ---------------------------------------------------------------------
// Validaciones del lado del servidor
// ---------------------------------------------------------------------
function valida_numero_cuenta(string $v): bool { return (bool) preg_match('/^\d{6,20}$/', $v); }
function valida_dpi(string $v): bool           { return (bool) preg_match('/^\d{13}$/', $v); }
function valida_usuario(string $v): bool       { return (bool) preg_match('/^[A-Za-z0-9._-]{4,30}$/', $v); }
function valida_nombre(string $v): bool
{
    return (bool) preg_match("/^[\p{L} .'-]{3,100}$/u", $v);
}
function valida_correo(string $v): bool
{
    return strlen($v) <= 120 && filter_var($v, FILTER_VALIDATE_EMAIL) !== false;
}
/** Mínimo 8 caracteres con mayúscula, minúscula y número. */
function valida_clave(string $v): bool
{
    return strlen($v) >= 8 && strlen($v) <= 72
        && preg_match('/[A-Z]/', $v) && preg_match('/[a-z]/', $v) && preg_match('/\d/', $v);
}
/** Devuelve el monto como string decimal con 2 decimales o null si no es válido. */
function monto_valido(string $v, bool $permitir_cero = false): ?string
{
    $v = str_replace([',', ' ', 'Q'], '', $v);
    if (!preg_match('/^\d{1,12}(\.\d{1,2})?$/', $v)) {
        return null;
    }
    if (!$permitir_cero && (float) $v <= 0) {
        return null;
    }
    return number_format((float) $v, 2, '.', '');
}

const MSJ_CLAVE = 'La contraseña debe tener al menos 8 caracteres e incluir mayúscula, minúscula y número.';

// ---------------------------------------------------------------------
// Autenticación y autorización por rol
// ---------------------------------------------------------------------
const ROLES = ['admin' => 'Administrador', 'cajero' => 'Cajero', 'usuario' => 'Cliente'];

function usuario_actual(): ?array
{
    return $_SESSION['auth'] ?? null;
}

function iniciar_sesion(string $rol, int $id, string $nombre, array $extra = []): void
{
    session_regenerate_id(true);
    $_SESSION['auth'] = array_merge(['rol' => $rol, 'id' => $id, 'nombre' => $nombre], $extra);
    $_SESSION['ultimo_movimiento'] = time();
    unset($_SESSION['intentos']);
}

function cerrar_sesion(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Exige que haya una sesión activa del rol indicado. */
function requiere_rol(string $rol): array
{
    $auth = usuario_actual();
    $login = ['admin' => 'admin/login.php', 'cajero' => 'cajero/login.php', 'usuario' => 'usuario/login.php'][$rol];

    if (!$auth || $auth['rol'] !== $rol) {
        flash('aviso', 'Debe iniciar sesión como ' . strtolower(ROLES[$rol]) . ' para continuar.');
        redirigir($login);
    }

    // Expiración por inactividad
    $ultimo = $_SESSION['ultimo_movimiento'] ?? 0;
    if (time() - $ultimo > APP_MINUTOS_SESION * 60) {
        cerrar_sesion();
        session_start();
        flash('aviso', 'Su sesión expiró por inactividad. Inicie sesión nuevamente.');
        redirigir($login);
    }
    $_SESSION['ultimo_movimiento'] = time();

    // Un cajero bloqueado pierde el acceso de inmediato
    if ($rol === 'cajero') {
        try {
            $c = sp_uno('sp_cajero_obtener_por_id', [$auth['id']], 'i');
        } catch (Throwable $e) {
            $c = null;
        }
        if (!$c || (int) $c['bloqueado'] === 1) {
            cerrar_sesion();
            session_start();
            flash('error', 'Su usuario de cajero está bloqueado. Comuníquese con el administrador.');
            redirigir($login);
        }
    }
    return $auth;
}

// Límite de intentos fallidos de inicio de sesión (por sesión y rol)
function login_en_espera(string $rol): int
{
    $i = $_SESSION['intentos'][$rol] ?? ['n' => 0, 't' => 0];
    if ($i['n'] >= APP_INTENTOS_LOGIN) {
        $resta = APP_ESPERA_LOGIN - (time() - $i['t']);
        if ($resta > 0) {
            return $resta;
        }
        unset($_SESSION['intentos'][$rol]);
    }
    return 0;
}

function login_fallido(string $rol): void
{
    $i = $_SESSION['intentos'][$rol] ?? ['n' => 0, 't' => 0];
    $_SESSION['intentos'][$rol] = ['n' => $i['n'] + 1, 't' => time()];
}

require_once __DIR__ . '/layout.php';
