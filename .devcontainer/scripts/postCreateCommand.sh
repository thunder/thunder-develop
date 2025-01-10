#!/bin/bash

# Install dependencies
composer install

# Setup docroot
DOCROOT=$(composer config extra.drupal-scaffold.locations.web-root)
echo "Setting up docroot: ${DOCROOT}"
sudo rm -rf /var/www/html
sudo ln -s "$(pwd)/${DOCROOT}" /var/www/html

# Start apache
apache2ctl restart
