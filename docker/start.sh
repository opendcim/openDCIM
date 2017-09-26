#!/bin/sh

sed -i "s|.*server_name.*|	server_name $SERVER_NAME;" /etc/nginx/conf.d/site.conf
sed -i "s|.*server_name.*|	server_name $SERVER_NAME;" /etc/nginx/conf.d/site-ssl.conf

supervisord --nodaemon --configuration /etc/supervisord.conf
