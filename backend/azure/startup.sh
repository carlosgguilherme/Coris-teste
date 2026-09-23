#!/bin/bash
cp /home/site/wwwroot/azure/nginx.conf /etc/nginx/sites-available/default
cp /home/site/wwwroot/azure/nginx.conf /etc/nginx/sites-enabled/default
service nginx reload

cd /home/site/wwwroot
php artisan migrate --force
php artisan config:cache
php artisan route:cache
