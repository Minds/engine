#!/bin/sh
set -e

cd /var/www/Minds/engine
php -n -c Spec/php-test.ini bin/phpspec $@
