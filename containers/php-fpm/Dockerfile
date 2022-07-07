FROM minds/php:pdo

ADD --chown=www-data . /var/www/Minds/engine

RUN rm -f /var/www/Minds/engine/settings.php \
    && mkdir --parents --mode=0777 /tmp/minds-cache/ \
    && mkdir --parents --mode=0777 /data/ \
    && echo 'zend_extension=opcache.so' > /usr/local/etc/php/conf.d/opcache.ini

COPY containers/php-fpm/pull-secrets.sh pull-secrets.sh
COPY containers/php-fpm/php.ini /usr/local/etc/php/
COPY containers/php-fpm/opcache.ini /usr/local/etc/php/conf.d/opcache-recommended.ini
COPY containers/php-fpm/apcu.ini /usr/local/etc/php/conf.d/docker-php-ext-apcu.ini
COPY containers/php-fpm/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

ARG MINDS_VERSION="Unknown" 
ENV MINDS_VERSION=${MINDS_VERSION}

ARG SENTRY_DSN=""
ENV SENTRY_DSN=${SENTRY_DSN}
