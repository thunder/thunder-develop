#!/usr/bin/env bash

composer config repositories.thunder git https://github.com/BurdaMagazinOrg/thunder-distribution.git
composer config repositories.admin git https://github.com/BurdaMagazinOrg/theme-thunder-admin.git
composer config repositories.paragraphs_features git https://github.com/thunder/paragraphs_features.git
composer install
/usr/bin/env PHP_OPTIONS="-d sendmail_path=`which true`" drush si thunder --root=docroot --db-url=mysql://travis@127.0.0.1/thunder -y
