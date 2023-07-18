#!/bin/bash

cd /var/www/html

printenv | grep "LL_" | sed 's/LL_//' > /var/www/html/.env

php artisan optimize:clear
php artisan optimize