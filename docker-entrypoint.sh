#!/bin/sh
# Defense-in-depth: never execute scripts out of the (mounted) uploads volume.
# Written at start because ./uploads is a bind mount excluded from the image/deploy.
cat > /var/www/html/uploads/.htaccess <<'EOF'
php_flag engine off
RemoveHandler .php .phtml .php3 .php4 .php5 .php7 .phar
RemoveType .php .phtml
Options -ExecCGI -Indexes
EOF

chown -R www-data:www-data /var/www/data
chown -R www-data:www-data /var/www/html/uploads
exec "$@"
