FROM php:7.4-apache

RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y locales

RUN sed -i -e 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' /etc/locale.gen && \
    dpkg-reconfigure --frontend=noninteractive locales && \
    update-locale LANG=en_US.UTF-8

ENV LANG en_US.UTF-8

RUN apt-get update -y && apt-get clean
RUN locale-gen en_US.UTF-8 && update-locale

RUN apt-get install mariadb-server -y

RUN locale-gen en_US

RUN docker-php-ext-install gettext

RUN apt-get install libsnmp-dev -y
RUN docker-php-ext-install snmp

RUN apt-get update \
	&& apt-get install -y \
		libfreetype6-dev \
		libpng-dev \
		libjpeg-dev \
	&& docker-php-ext-configure gd \
	&& docker-php-ext-install -j$(nproc) \
		gd \
	&& apt-get purge -y \
		libfreetype6-dev \
		libpng-dev \
		libjpeg-dev

RUN apt-get update \
	&& apt-get install -y \
		libzip-dev \
	&& docker-php-ext-install -j$(nproc) \
		zip \
	&& apt-get purge -y \
		libzip-dev

RUN docker-php-ext-install pdo_mysql

RUN a2enmod rewrite && a2enmod headers

USER www-data

COPY docker-build/files/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY ./ /var/www/html

USER root

RUN htpasswd -b -c /var/www/html/.htpasswd opendcim opendcim

RUN rm -fr /var/www/html/docker-build/
RUN rm -f /var/www/html/Dockerfile
RUN chown -fR www-data:www-data /var/www/html/