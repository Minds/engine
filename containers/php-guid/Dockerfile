FROM minds/php:7.3

# Copy our built the code

ADD --chown=www-data . /var/www/Minds/engine

# Remove the local settings file (if it exists)

RUN rm -f /var/www/Minds/engine/settings.php

# Setup our supervisor service

RUN apk add --no-cache \
        supervisor&& \
    mkdir /etc/supervisor && \
    mkdir /etc/supervisor/conf.d

COPY ./containers/php-guid/supervisord.conf /etc
COPY ./containers/php-guid/guid.conf /etc/supervisor/conf.d

ENTRYPOINT ["supervisord", "--nodaemon", "--configuration", "/etc/supervisord.conf"]
