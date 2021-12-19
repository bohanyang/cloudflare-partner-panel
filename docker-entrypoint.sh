#!/usr/bin/env sh

set -eu

sed -Ei "s,^Listen [0-9]+,Listen $PORT," /etc/apache2/ports.conf
sed -Ei "s,^<VirtualHost \*:[0-9]+>,<VirtualHost *:$PORT>," /etc/apache2/sites-available/000-default.conf

a2dismod mpm_event || :
a2dismod mpm_worker || :
a2enmod mpm_prefork || :

exec docker-php-entrypoint "$@"
