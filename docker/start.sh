#!/usr/bin/env sh
set -e

cd /var/www/html

if [ -n "$MYSQL_ATTR_SSL_CA_CONTENT" ]; then
    printf "%s\n" "$MYSQL_ATTR_SSL_CA_CONTENT" > /tmp/mysql-ca.pem
    export MYSQL_ATTR_SSL_CA=/tmp/mysql-ca.pem
fi

php artisan config:clear
php artisan migrate --force
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
