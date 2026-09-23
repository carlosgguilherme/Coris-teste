#!/bin/bash
cp /home/site/wwwroot/azure/nginx.conf /etc/nginx/sites-available/default
cp /home/site/wwwroot/azure/nginx.conf /etc/nginx/sites-enabled/default
service nginx reload

php /home/site/wwwroot/bin/migrate.php
php /home/site/wwwroot/bin/criar-admin.php
