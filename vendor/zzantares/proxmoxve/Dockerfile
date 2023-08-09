FROM php:5-alpine

RUN wget https://raw.githubusercontent.com/composer/getcomposer.org/f084c2e65e0bf3f3eac0f73107450afff5c2d666/web/installer -O - -q | php -- --quiet
RUN mv composer.phar /usr/local/bin/composer
RUN mkdir -p /root/proxmoxve/

ENTRYPOINT ["sh"]

WORKDIR /root/proxmoxve/
