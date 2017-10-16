FROM php:fpm-alpine

MAINTAINER Pouyan Heyratpour <pouyan@cloudzero.ir>

# Env
ENV PHP_CONF /usr/local/etc/php-fpm.conf
ENV FPM_CONF /usr/local/etc/php-fpm.d/www.conf
ENV PHP_VARS /usr/local/etc/php/conf.d/docker-vars.ini

# Install PHP Modules
RUN apk update && \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS && \
		apk add --no-cache icu-dev openldap-dev net-snmp-dev gettext-dev && \
    docker-php-ext-install pdo_mysql intl snmp ldap gettext && \
    apk del .build-deps && \
    rm -rf /tmp/* /var/cache/apk/*

# Config php
RUN LOCALE="en-US" && \
		echo "intl.default_locale = ${LOCALE}" >> $PHP_INI_DIR/conf.d/docker-php-ext-intl.ini && \
		echo "cgi.fix_pathinfo=0" > ${PHP_VARS} && \
		echo "upload_max_filesize = 100M"  >> ${PHP_VARS} && \
		echo "post_max_size = 100M"  >> ${PHP_VARS} && \
		echo "variables_order = \"EGPCS\""  >> ${PHP_VARS} && \
		echo "memory_limit = 128M"  >> ${PHP_VARS} && \
		sed -i \
				-e "s/;catch_workers_output\s*=\s*yes/catch_workers_output = yes/g" \
				-e "s/pm.max_children = 5/pm.max_children = 4/g" \
				-e "s/pm.start_servers = 2/pm.start_servers = 3/g" \
				-e "s/pm.min_spare_servers = 1/pm.min_spare_servers = 2/g" \
				-e "s/pm.max_spare_servers = 3/pm.max_spare_servers = 4/g" \
				-e "s/;pm.max_requests = 500/pm.max_requests = 200/g" \
				-e "s/;listen.mode = 0660/listen.mode = 0666/g" \
				-e "s/listen = 127.0.0.1:9000/listen = \/var\/run\/php-fpm.sock/g" \
				-e "s/^;clear_env = no$/clear_env = no/" \
				${FPM_CONF}

# Install Timezone
RUN apk add --no-cache tzdata && \
		TIMEZONE="Iran" && \
    ln -sf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && \
    echo "${TIMEZONE}" > /etc/timezone && \
    echo "date.timezone = ${TIMEZONE}" > $PHP_INI_DIR/conf.d/timezone.ini

# Install Nginx
RUN apk add --no-cache nginx && \
		chown -R www-data:www-data /var/www/html && \
		rm -rf /tmp/* /var/cache/apk/* && \
		mv /etc/nginx/nginx.conf /etc/nginx/nginx.conf.orig && \
		ln -sf /dev/stdout /var/log/nginx/access.log && \
    ln -sf /dev/stderr /var/log/nginx/error.log

# Add Nginx Configurations
ADD docker/nginx.conf /etc/nginx/nginx.conf
ADD docker/site.conf /etc/nginx/conf.d/site.conf
ADD docker/fastcgi_params /etc/nginx/fastcgi_params

# Install supervisor
RUN apk add --no-cache supervisor && \
		rm -rf /tmp/* /var/cache/apk/*

# Add Supervisor Configurations
ADD docker/supervisord.conf /etc/supervisord.conf

# Scripts
ADD docker/start.sh /
RUN chmod a+x /start.sh

ENTRYPOINT /start.sh

# Add Source
ADD . /var/www/html/
