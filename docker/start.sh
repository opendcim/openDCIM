#!/bin/sh

sed -i "s|.*server_name.*|	server_name $SERVER_NAME;|" /etc/nginx/conf.d/site.conf
sed -i "s|.*server_name.*|	server_name $SERVER_NAME;|" /etc/nginx/conf.d/site-ssl.conf
sed -i "s|.*\$dbhost.*=.*|	\$dbhost = '$MYSQL_HOST';|" /var/www/html/db.inc.php

supervisord --nodaemon --configuration /etc/supervisord.conf
