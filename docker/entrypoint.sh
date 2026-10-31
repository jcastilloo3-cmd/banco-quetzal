#!/bin/sh
set -e

PORT="${PORT:-10000}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

if [ -n "$DB_CA_CERT" ]; then
  printf '%s\n' "$DB_CA_CERT" > /etc/ssl/certs/aiven-ca.pem
  export DB_SSL_CA=/etc/ssl/certs/aiven-ca.pem
fi

if [ "$DB_REINSTALAR" = "1" ]; then
  php /var/www/html/database/instalar.php --forzar || echo "[entrypoint] Aviso: no se pudo reinstalar la BD."
else
  php /var/www/html/database/instalar.php || echo "[entrypoint] Aviso: no se pudo instalar la BD (revise las variables DB_*)."
fi

exec "$@"
