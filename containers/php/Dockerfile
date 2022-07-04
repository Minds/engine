FROM php:8.0-fpm-alpine3.15

RUN apk add --no-cache --update --virtual .php-deps make

RUN apk add --no-cache --virtual build-deps \
    libzip-dev \
    zlib-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    $PHPIZE_DEPS \
    && apk add --no-cache \
    libzip \
    coreutils \
    imagemagick \
    nodejs \
    npm \
    ffmpeg \
    icu-dev \
    gmp-dev \
    && docker-php-ext-install -j$(nproc) bcmath \
    && docker-php-ext-install -j$(nproc) zip \
    && docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install -j$(nproc) exif \
    && docker-php-ext-install -j$(nproc) sockets \
    && docker-php-ext-install -j$(nproc) intl \
    && docker-php-ext-install -j$(nproc) gmp \
    && apk del build-deps

# PECL Extensions
RUN apk add --no-cache --virtual build-deps \
    $PHPIZE_DEPS \
    imagemagick-dev \
    && pecl install redis \
    && pecl install apcu \
    && pecl install imagick \
    && docker-php-ext-enable redis \
    && docker-php-ext-enable apcu \
    && docker-php-ext-enable imagick \
    && apk del build-deps

# Cassandra extension
ENV MAKEFLAGS -j4
ENV INSTALL_DIR /usr/src/datastax-php-driver
ENV BUILD_DEPS \
    bash \
    cmake \
    autoconf \
    g++ \
    gcc \
    make \
    pcre-dev \
    libuv-dev \
    git \
    gmp-dev \
    autoconf \
    libtool \
    openssl-dev \
    zlib-dev \
    $PHPIZE_DEPS

RUN apk add --no-cache --virtual build-deps $BUILD_DEPS \
    && apk add --no-cache libuv gmp \
    && git clone --branch=v1.3.x https://github.com/nano-interactive/php-driver.git $INSTALL_DIR \
    && cd $INSTALL_DIR \
    && git submodule update --init \
    # Install CPP Driver
    && cd $INSTALL_DIR/lib/cpp-driver \
    && mkdir build && cd build \
    && cmake -DCASS_BUILD_STATIC=ON -DCASS_BUILD_SHARED=ON .. \
    && make && make install \
    # Install PHP Driver
    && cd $INSTALL_DIR/ext \
    && phpize && ./configure && make && make install \
    && docker-php-ext-enable cassandra \
    && apk del build-deps \
    && rm -rf $INSTALL_DIR

# blurhash extension
RUN apk add --no-cache --virtual build-deps $BUILD_DEPS \
    && curl -fsSL 'https://gitlab.com/minds/php-ext-blurhash/-/archive/master/php_ext_blurhash-master.tar.gz' -o blurhash.tar.gz \
    && mkdir -p blurhash \
    && tar -xf blurhash.tar.gz -C blurhash --strip-components=1 \
    && rm blurhash.tar.gz \
    && ( \
        cd blurhash \
        && phpize \
        && ./configure\
        && make -j "$(nproc)" \
        && make install \
    ) \
    && rm -r blurhash \
    && docker-php-ext-enable blurhash \
    && apk del build-deps

# ZMQ extension
ENV INSTALL_DIR /usr/src/php-zmq
RUN apk add --no-cache --virtual build-deps \
    zeromq-dev \
    git \
    $PHPIZE_DEPS \
    && apk add --no-cache zeromq \
    && git clone https://github.com/zeromq/php-zmq.git $INSTALL_DIR \
    && cd $INSTALL_DIR \
    && phpize \
    && ./configure \
    && make -j$(nproc) \
    && make install \
    && docker-php-ext-enable zmq \
    && apk del build-deps \
    && rm -rf $INSTALL_DIR

# Install awscli (downstream containers require for the moment)

RUN apk update && apk add --no-cache py-pip && pip install --upgrade pip && pip install awscli

# Build Pulsar

ENV BUILD_DEPS \
    bash \
    cmake \
    autoconf \
    g++ \
    gcc \
    make \
    pcre-dev \
    libuv-dev \
    git \
    gmp-dev \
    autoconf \
    libtool \
    openssl-dev \
    zlib-dev \
    boost-dev \
    py3-setuptools \
    python3-dev \ 
    protobuf-dev \
    curl-dev \
    gtest-dev gmock \
    $PHPIZE_DEPS

RUN apk add --no-cache --virtual build-deps $BUILD_DEPS \
    # PHP CPP
    && git clone https://github.com/Minds/PHP-CPP.git \
    && cd PHP-CPP \
    && git checkout php80 \
    && make -j4 \
    && make install \
    && cd .. \
    # Pulsar CPP Client
    && git clone --depth 1 https://github.com/apache/pulsar.git \
    && cd pulsar/pulsar-client-cpp \
    && cmake . -DBUILD_TESTS=OFF \
    && make -j2 \
    && make install \
    && cd ../.. \
    && git clone https://gitlab.com/minds/pulsar-php-client \
    && cd pulsar-php-client \
    && make -j4 \
    && cp output/pulsar.so $(php-config --extension-dir)/pulsar.so \
    && cd .. \
    && rm -rf PHP-CPP pulsar pulsar-php-client \
    && apk del build-deps

RUN apk add protobuf-dev automake


RUN apk add --no-cache --virtual build-deps $BUILD_DEPS \
    # libsecp256kq
    && git clone https://github.com/bitcoin-core/secp256k1.git \
    && cd secp256k1 \
        && git checkout efad3506a8937162e8010f5839fdf3771dfcf516 \
    && ./autogen.sh \
    && ./configure --enable-tests=no --enable-benchmark=no --enable-experimental --enable-module-ecdh --enable-module-recovery --enable-module-schnorrsig --enable-module-extrakeys \
    && make \
    && make install \
    && cd .. \
    # secp256k1-php
    && git clone https://github.com/Minds/secp256k1-php.git --branch fix-php8-schnorrsig \
    && cd secp256k1-php/secp256k1 \
    && phpize \
    && ./configure --with-secp256k1-config --with-module-recovery --with-module-ecdh --with-module-schnorrsig --with-module-extrakeys \
    && make \
    && make install \
    && docker-php-ext-enable secp256k1 \
    && cd ../../ \
    && rm -rf secp256k1 secp256k1-php \
    && apk del build-deps

# Install PDO
RUN docker-php-ext-install pdo pdo_mysql

# PHP INI
COPY php.ini /usr/local/etc/php/
COPY opcache.ini /usr/local/etc/php/conf.d/opcache-recommended.ini
COPY pulsar.ini /usr/local/etc/php/conf.d/pulsar.ini

WORKDIR /var/www/Minds
