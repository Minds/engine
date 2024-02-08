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
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === 'edb40769019ccf227279e3bdd1f5b2e9950eb000c3233ee85148944e555d97be3ea4f40c3c2fe73b22f875385f6a5155') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"

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
