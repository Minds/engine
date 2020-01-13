#!/bin/sh

INSTALLOPTS=""
if [ "$1" == "production" ]; then
  INSTALLOPTS="-a"
fi

# Clear vendor cache
rm -rf ../vendor

# Setup composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('SHA384', 'composer-setup.php') === 'c5b9b6d368201a9db6f74e2611495f369991b72d9c8cbd3ffbc63edff210eb73d46ffbfce88669ad33695ef77dc76976') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"

# Optimise for package install speed
composer.phar -n global require -n "hirak/prestissimo"
# Grab dependencies
php composer.phar install $INSTALLOPTS --ignore-platform-reqs
