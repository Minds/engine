#!/bin/sh

# Exit script wit ERRORLEVEL if any command fails
set -e

INSTALLOPTS=""
if [ "$1" == "production" ]; then
  INSTALLOPTS="-a"
fi

# Clear vendor cache
rm -rf ../vendor

# Keep current directory ref
CURRENT_DIR=`pwd`

# Setup composer
# Hash update information - https://getcomposer.org/download/
EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]
then
    >&2 echo 'ERROR: Invalid installer checksum'
    rm composer-setup.php
    exit 1
fi

# Got back to current dir if changed
cd $CURRENT_DIR


# Optimise for package install speed
# php composer.phar -n global require -n "hirak/prestissimo"

# Grab dependencies
php composer.phar install $INSTALLOPTS --ignore-platform-reqs

apk add --virtual .bdeps npm

# Issue with composer plugin not firing for mw3 package
npm --prefix ./vendor/minds/mw3 install

apk del .bdeps
