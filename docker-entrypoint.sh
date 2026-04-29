#!/bin/sh
set -e

mkdir -p /var/www/html/data
php /var/www/html/seed_local_db.php

exec apache2-foreground
