#!/usr/bin/env sh

set -eu

sed -Ei "s,^Listen 80,Listen $PORT," /etc/apache2/ports.conf
sed -Ei "s,^<VirtualHost \*:80>,<VirtualHost *:$PORT>," /etc/apache2/sites-available/000-default.conf

exec docker-php-entrypoint "$@"
