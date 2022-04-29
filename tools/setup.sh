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
php -r "if (hash_file('sha384', 'composer-setup.php') === '55ce33d7678c5a611085589f1f3ddf8b3c52d662cd01d4ba75c0ee0459970c2200a51f492d557530c71c15d8dba01eae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"

# Got back to current dir if changed
cd $CURRENT_DIR


# Optimise for package install speed
# php composer.phar -n global require -n "hirak/prestissimo"

# Grab dependencies
php composer.phar install $INSTALLOPTS --ignore-platform-reqs
