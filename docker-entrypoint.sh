#!/bin/sh
chown -R www-data:www-data /var/www/data
chown -R www-data:www-data /var/www/html/uploads
exec "$@"
