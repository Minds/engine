#!/bin/sh

# Exit script wit ERRORLEVEL if any command fails
set -e

# Keep current directory ref
CURRENT_DIR=$(pwd)

# Got back to current dir if changed
cd "$CURRENT_DIR/integration_tests"

# Clear vendor cache
rm -rf ./vendor

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

php composer-setup.php --quiet
php -r "unlink('composer-setup.php');"

php composer.phar install

cp "$ENGINE_INTEGRATION_TESTS_CONFIG" .env

php bin/codecept build

php bin/codecept run
