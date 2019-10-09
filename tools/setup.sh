#!/bin/sh

INSTALLOPTS=""
if [ "$1" == "production" ]; then
  INSTALLOPTS="-a"
fi

# Clear vendor cache
rm -rf ../vendor

# Setup composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('SHA384', 'composer-setup.php') === 'a5c698ffe4b8e849a443b120cd5ba38043260d5c4023dbf93e1558871f1f07f58274fc6f4c93bcfd858c6bd0775cd8d1') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"

# Optimise for package install speed
composer.phar -n global require -n "hirak/prestissimo"
# Grab dependencies
php composer.phar install $INSTALLOPTS --ignore-platform-reqs
