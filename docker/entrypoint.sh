#!/bin/sh
set -e

# Render asigna el puerto en $PORT
PORT="${PORT:-10000}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Certificado de Aiven (opcional): si se pega el contenido en DB_CA_CERT se guarda como archivo
if [ -n "$DB_CA_CERT" ]; then
  printf '%s\n' "$DB_CA_CERT" > /etc/ssl/certs/aiven-ca.pem
  export DB_SSL_CA=/etc/ssl/certs/aiven-ca.pem
fi

# Las variables de entorno del contenedor (DB_*, etc.) las lee PHP con getenv().

# Crea las tablas y procedimientos la primera vez (si la BD está vacía).
# Con DB_REINSTALAR=1 borra todo y deja la BD en blanco (quitar la variable después).
if [ "$DB_REINSTALAR" = "1" ]; then
  php /var/www/html/database/instalar.php --forzar || echo "[entrypoint] Aviso: no se pudo reinstalar la BD."
else
  php /var/www/html/database/instalar.php || echo "[entrypoint] Aviso: no se pudo instalar la BD (revise las variables DB_*)."
fi

exec "$@"
