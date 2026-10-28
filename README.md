# Banco Quetzal — Sistema de Banca en Línea

Proyecto del curso **Desarrollo Web** (UMG) — HTML5 / CSS3 / PHP / MySQL.
Autor: **Jorge Otoniel Castillo Ortega** — carné 0900-13-719.

## Credenciales iniciales (BD en blanco)

| Rol | Usuario | Contraseña |
|---|---|---|
| Administrador | `admin` | `BancoQ2026!` |

Los cajeros los crea el administrador; las cuentas, los cajeros; y los usuarios cliente se registran solos en "Registro de nuevo usuario".

## Estructura

```
banco-quetzal/
├── index.php              Página principal (logo, nombre y 4 enlaces)
├── registro.php           Registro de nuevo usuario
├── logout.php, 404.php
├── admin/                 login, panel, cajeros, monitor, log de Apache (GoAccess)
├── cajero/                login, panel, crear cuenta, depósito, retiro
├── usuario/               login, mi cuenta, terceros, transferir, estado de cuenta
├── includes/              bootstrap (sesión, CSRF, acceso a SPs), plantilla, gráficas, análisis de log
├── config/config.php      configuración (variables de entorno o valores de WampServer)
├── assets/                CSS responsivo, JS y logo SVG
├── database/
│   ├── banco_quetzal.sql  tablas + 21 procedimientos almacenados + admin
│   └── instalar.php       instalador por consola (lo usa Render)
├── Dockerfile, docker/, render.yaml   despliegue en Render
└── storage/               reporte GoAccess (no accesible por web)
```

## Instalación local (WampServer)

1. Copiar la carpeta `banco-quetzal` a `C:\wamp64\www\`.
2. Abrir phpMyAdmin (http://localhost/phpmyadmin) → **Importar** → `database/banco_quetzal.sql` → Continuar.
   (Crea la base `banco_quetzal`, sus tablas, los procedimientos y el usuario admin.)
3. Si el usuario `root` de MySQL tiene contraseña, cambiar `DB_PASS` en `config/config.php`.
4. Abrir http://localhost/banco-quetzal/

## Despliegue en Render + Aiven (MySQL gratis)

1. **Aiven** (https://console.aiven.io): crear servicio **MySQL – Free plan**. Copiar Host, Port, User (`avnadmin`), Password.
2. **GitHub**: subir esta carpeta a un repositorio (p. ej. `jcastilloo3-cmd/banco-quetzal`).
3. **Render** → New → **Blueprint** → elegir el repositorio. Render lee `render.yaml` y pide
   `DB_HOST`, `DB_PORT`, `DB_PASS` (y opcionalmente `DB_CA_CERT`). Deploy.
4. Al arrancar, el contenedor ejecuta `database/instalar.php`, que crea tablas y procedimientos si la BD está vacía.
5. Abrir `https://banco-quetzal.onrender.com` (el plan gratis se duerme tras 15 min sin tráfico; la primera visita tarda ~1 min).

## Seguridad implementada

* Todo acceso a datos por **procedimientos almacenados** con parámetros enlazados (`CALL sp_x(?, ?)`).
* Contraseñas con `password_hash` (bcrypt); nunca en texto plano.
* Sesiones con cookie HttpOnly/SameSite, regeneración de ID al iniciar sesión, expiración por inactividad (15 min).
* Token **CSRF** en todos los formularios POST; escape de salida con `htmlspecialchars`.
* Límite de 5 intentos fallidos de inicio de sesión (espera de 5 min).
* Autorización por rol en cada página; un cajero bloqueado pierde el acceso de inmediato.
* Transferencia dentro de `START TRANSACTION … COMMIT` con `ROLLBACK` ante cualquier error y bloqueo `FOR UPDATE`.
* Cabeceras: CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy.
* Validación en cliente (HTML5) y en servidor (PHP + reglas de negocio en los SPs).
