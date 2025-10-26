#!/bin/bash

# instalation for RedHat
# testing in RedHat 8.10

if [ ! -f openDCIMdevicetemplate.xsd ]; then
    echo "Please run this script from the openDCIM base directory"
    exit 1
fi

if [ "$(grep 'Red Hat Enterprise Linux 8.10' /etc/os-release)" ]; then
    # dnf update -y # uncomment to update all packages
    dnf install -y dnf-utils epel-release
    dnf install -y https://dl.fedoraproject.org/pub/epel/epel-release-latest-8.noarch.rpm
    subscription-manager repos --enable codeready-builder-for-rhel-8-x86_64-rpms

    PHP_LAST_VERSION=$(dnf module list php | grep -P '^php' | awk '{print $2}' | tail -1)

    dnf module reset php -y
    dnf module enable php:$PHP_LAST_VERSION -y
    dnf module list php
    dnf install -y \
        httpd \
        graphviz net-snmp-utils openldap-clients \
        glibc-langpack-ca glibc-langpack-cs glibc-langpack-de glibc-langpack-en glibc-langpack-es glibc-langpack-fr glibc-langpack-gl glibc-langpack-it glibc-langpack-ja glibc-langpack-ko glibc-langpack-lv glibc-langpack-pl glibc-langpack-pt glibc-langpack-ru glibc-langpack-sk glibc-langpack-sl glibc-langpack-uk glibc-langpack-zh \
        php php-common php-cli php-fpm php-devel \
        php-mysqlnd php-pgsql php-pdo php-odbc php-dba \
        php-xml php-mbstring php-gd \
        php-snmp php-ldap php-soap php-curl \
        php-bcmath php-dbg php-embedded php-enchant php-ffi php-gmp php-intl php-opcache php-process \
        php-pear php-pecl-apcu php-pecl-rrd php-pecl-xdebug3 php-pecl-zip \
        MariaDB-server MariaDB-client MariaDB-common MariaDB-backup MariaDB-devel MariaDB-compat \
        MariaDB-connect-engine MariaDB-oqgraph-engine MariaDB-rocksdb-engine MariaDB-s3-engine \
        MariaDB-cracklib-password-check MariaDB-gssapi-server MariaDB-shared MariaDB-test
    dnf update httpd php -y

    if [ ! "$(grep -P '^magic_quotes_gpc' /etc/php.ini)" ]; then
        (
            echo
            echo 'magic_quotes_gpc = Off'
        ) >> /etc/php.ini
    fi
    if [ ! "$(grep -P '^ServerName ' /etc/httpd/conf/httpd.conf)" ]; then
        (
            echo
            echo "ServerName $HOSTNAME:443"
        ) >> /etc/httpd/conf/httpd.conf
    fi
    (
        echo 'Check /etc/httpd/conf/httpd.conf configurations:'
        echo '1. Ensure the following modules are loaded:'
        echo '    <Directory "/var/www/html">'
        echo '        AllowOverride All'
        echo '        Require all granted'
        echo '    </Directory>'
        echo '2. Ensure SSL is configured and enabled.'
        echo 'run `systemctl restart httpd`'
    )
    
    CERT_NAME='dcim'
    THIS_IP=$(hostname -I | awk '{print $1}')
    THIS_PATH=$(pwd)
    openssl req -newkey rsa:2048 -nodes -keyout /etc/pki/tls/private/$CERT_NAME.key -x509 -days 365 -out /etc/pki/tls/certs/$CERT_NAME.crt -subj "/C=BR/ST=SP/L=SaoPaulo/O=Local/OU=TI/CN=$THIS_IP"
    (
        echo "<VirtualHost *:443>"
        echo "    ServerName $THIS_IP"
        echo "    DocumentRoot $THIS_PATH"
        echo "    ServerAdmin email@domain.com.br"
        echo "    SSLEngine on"
        echo "    SSLCertificateFile /etc/pki/tls/certs/$CERT_NAME.crt"
        echo "    SSLCertificateKeyFile /etc/pki/tls/private/$CERT_NAME.key"
        echo "    SSLCertificateChainFile /etc/pki/tls/certs/ca.crt"
        echo "    <Directory $THIS_PATH>"
        echo "        AllowOverride All"
        echo "        Require all granted"
        echo "        # AuthType Basic"
        echo '        # AuthName "openDCIM"'
        echo "        # AuthUserFile $THIS_PATH/.htpasswd"
        echo "        # Require valid-user"
        echo "    </Directory>"
        echo "</VirtualHost>"
    ) > "/etc/httpd/conf.d/$CERT_NAME.conf"

    # localectl set-locale LANG=pt_BR.UTF-8
    systemctl enable httpd
    systemctl start httpd
    systemctl enable mariadb
    systemctl start mariadb

    systemctl status httpd
    ldapsearch -V
    snmpget --version
    mysql --version
    localectl list-locales
else
    echo "This script is only for Red Hat Enterprise Linux 8.10"
    exit 1
fi

mkdir -p assets/{pictures,drawings,reports}
mkdir -p elogs
chown -R  root.root *
find . -mindepth 1 -maxdepth 1 -type d -exec chmod 775 {} \;
chown -R apache.apache assets/*
chmod 777 assets/*

mysql -uroot -p -e "CREATE DATABASE dcim;CREATE USER 'dcim'@'127.0.0.1' IDENTIFIED BY 'dcim';GRANT ALL ON dcim.* TO 'dcim'@'127.0.0.1';FLUSH PRIVILEGES;"

composer update -q

cp db.inc.php-dist db.inc.php
echo 'Created db.inc.php from db.inc.php-dist.'
echo 'Please edit this file to configure your database connection and authentication method.'
