#!/bin/sh

# Exit script wit ERRORLEVEL if any command fails
set -e

# Keep current directory ref
CURRENT_DIR=`pwd`

# Got back to current dir if changed
cd $CURRENT_DIR

apk add --virtual .bdeps npm

# Issue with composer plugin not firing for mw3 package
npm --prefix ./vendor/minds/mw3 install

apk del .bdeps
