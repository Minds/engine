#!/bin/sh
set -e

if [ $ENABLE_XDEBUG = "true" ]; then
    echo "Enabling xdebug"

    apk add --update linux-headers
    apk add --virtual .bdeps $PHPIZE_DEPS \
        && pecl install xdebug \
        && docker-php-ext-enable xdebug
fi

cd /var/www/Minds/engine
php-fpm $@