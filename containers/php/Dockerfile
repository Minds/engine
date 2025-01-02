FROM php:8.2-fpm-alpine3.19


# Install AWS CLI
RUN apk update \
    && apk upgrade \
    && apk add --update --no-cache --virtual .php-deps make \
    && apk add aws-cli \
    && rm -rf /var/cache/apk/*

RUN apk add --virtual build-deps \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    imagemagick-dev \
    $PHPIZE_DEPS \
    && apk add libzip \
    gmp-dev \
    icu-libs \
    libpng \
    libjpeg \
    libwebp-dev \
    nodejs \
    linux-headers \
    # Install FFMpeg
    ffmpeg \
    # Install PHP extension
    && docker-php-ext-install -j$(nproc) bcmath \
    && docker-php-ext-install -j$(nproc) zip \
    && docker-php-ext-configure gd --with-freetype=/usr/include/ --with-webp=/usr/include/ --with-jpeg=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install -j$(nproc) exif \
    && docker-php-ext-install -j$(nproc) sockets \
    && docker-php-ext-install -j$(nproc) intl \
    && docker-php-ext-install -j$(nproc) gmp \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql \
    # PECL Extensions
    && apk add libgomp \
    imagemagick-libs \
    imagemagick-webp \
    imagemagick \
    && pecl install redis \
    && pecl install apcu \
    && pecl install imagick \
    && docker-php-ext-enable redis \
    && docker-php-ext-enable apcu \
    && docker-php-ext-enable imagick \
    # Cleanup
    && apk del build-deps \
    # starting libsecp256kq php extension compile
    && export BUILD_DEPS=$'\
    cmake \
    pcre-dev \
    libuv-dev \
    git \
    gmp-dev \
    libtool \
    openssl-dev \
    zlib-dev \
    boost-dev \
    py3-setuptools \
    python3-dev \
    protobuf-dev \
    curl-dev \
    gtest-dev gmock \
    automake' \
    # SECP256K1 signing
    && apk add --virtual build-deps $BUILD_DEPS $PHPIZE_DEPS \
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
    && apk del build-deps \
    && export BUILD_DEPS=$' \
    cmake \
    make \
    pcre-dev \
    libuv-dev \
    git \
    gmp-dev \
    libtool \
    openssl-dev \
    libstdc++ \
    zlib-dev \
    unzip \
    mlocate \
    ninja-build \
    ninja \
    openssl' \
    && export INSTALL_DIR=/usr/src/datastax-php-driver \
    # Cassandra extension \
    && apk add --no-cache --virtual build-deps $BUILD_DEPS $PHPIZE_DEPS \
    && apk add --no-cache libuv gmp libstdc++ \
    && git clone --branch=v1.3.x https://github.com/he4rt/scylladb-php-driver.git $INSTALL_DIR \
    && cd $INSTALL_DIR \
    && git checkout 1cf12c5ce49ed43a2c449bee4b7b23ce02a37bf0 \
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
    && rm -rf $INSTALL_DIR $DRIVER_INSTALL_DIR \
    # blurhash extension
    && cd $WORKDIR \
    && apk add --virtual build-deps $BUILD_DEPS $PHPIZE_DEPS \
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
    && apk del build-deps \
    # ZMQ extension
    && export INSTALL_DIR=/usr/src/php-zmq \
    && apk add --virtual build-deps \
    zeromq-dev \
    git \
    $PHPIZE_DEPS \
    && apk add --no-cache zeromq \
    && git clone https://github.com/zeromq/php-zmq.git $INSTALL_DIR \
    && cd $INSTALL_DIR \
    && pwd \
    && ls -la \
    && phpize \
    && ./configure \
    && make -j$(nproc) \
    && make install \
    && docker-php-ext-enable zmq \
    && rm -rf $INSTALL_DIR \
    && cd .. \
    && apk del build-deps \
    # Pulsar build deps
    && apk add --no-cache --virtual build-deps \
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
    build-base \
    curl-dev \
    gtest-dev gmock \
    libstdc++ \
    $PHPIZE_DEPS \
    # PHP CPP
    && export INSTALL_DIR=/usr/src/php-cpp \
    && git clone https://github.com/fast-debug/PHP-CPP.git $INSTALL_DIR \
    && cd $INSTALL_DIR \
    && make -j4 COMPILER_FLAGS="-Wall -c -std=c++17 -fvisibility=hidden -DBUILDING_PHPCPP -Wno-write-strings -MD -g" \
    && make install \
    && rm -rf $INSTALL_DIR \
    && cd .. \
    # Pulsar CPP Client
    && export INSTALL_DIR=/usr/src/pulsar-client-cpp \
    && git clone --depth 1 https://github.com/apache/pulsar-client-cpp.git $INSTALL_DIR \
    && echo "cloned" \
    && cd $INSTALL_DIR \
    && cmake . -DINTEGRATE_VCPKG=OFF -DCMAKE_CXX_STANDARD=17 -DBUILD_TESTS=OFF \
    && make -j2 \
    && make install \
    && rm -rf $INSTALL_DIR \
    && cd .. \
    # Pulsar PHP Client
    && export INSTALL_DIR=/usr/src/pulsar-php-client \
    && apk add protobuf-dev \
    && git clone https://gitlab.com/minds/pulsar-php-client $INSTALL_DIR \
    && cd $INSTALL_DIR \
    && make -j4 \
    && cp output/pulsar.so $(php-config --extension-dir)/pulsar.so \
    && rm -rf $INSTALL_DIR \
    && apk del build-deps

# PHP INI
COPY php.ini /usr/local/etc/php/
COPY opcache.ini /usr/local/etc/php/conf.d/opcache-recommended.ini
COPY pulsar.ini /usr/local/etc/php/conf.d/pulsar.ini

WORKDIR /var/www/Minds
