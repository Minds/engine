#!/bin/sh

# Exit script wit ERRORLEVEL if any command fails
set -e

INSTALLOPTS=""
if [ "$1" == "production" ]; then
  INSTALLOPTS="-a"
fi

# Clear vendor cache
rm -rf ../vendor

# Setup composer
# Hash update information - https://getcomposer.org/download/
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === 'c31c1e292ad7be5f49291169c0ac8f683499edddcfd4e42232982d0fd193004208a58ff6f353fde0012d35fdd72bc394') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php --version=1.10.16
php -r "unlink('composer-setup.php');"

# Optimise for package install speed
php composer.phar -n global require -n "hirak/prestissimo"

# Grab dependencies
php composer.phar install $INSTALLOPTS --ignore-platform-reqs
