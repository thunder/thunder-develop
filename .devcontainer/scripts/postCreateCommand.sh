#!/bin/bash

# Install dependencies
composer install

# Setup docroot
DOCROOT=$(composer config extra.drupal-scaffold.locations.web-root)
sudo chmod a+x "$(pwd)"
sudo rm -rf /var/www/html
sudo ln -s "$(pwd)/${DOCROOT}" /var/www/html
