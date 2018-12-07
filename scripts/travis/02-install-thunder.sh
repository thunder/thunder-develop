#!/usr/bin/env bash

composer config repositories.thunder git https://github.com/BurdaMagazinOrg/thunder-distribution.git
composer config repositories.admin git https://github.com/BurdaMagazinOrg/theme-thunder-admin.git
composer config repositories.paragraphs_features git https://github.com/thunder/paragraphs_features.git
composer config repositories.custom_list git https://github.com/thunder/custom_list.git
composer config repositories.select2 git https://github.com/thunder/select2.git
composer config repositories.riddle git https://github.com/BurdaMagazinOrg/module-riddle_marketplace.git
composer config repositories.ivw git https://github.com/BurdaMagazinOrg/module-ivw_integration.git
composer config repositories.update_helper git https://github.com/BurdaMagazinOrg/module-update_helper.git
composer install

/usr/bin/env PHP_OPTIONS="-d sendmail_path=`which true`" vendor/bin/drush si thunder --root=docroot --db-url=mysql://travis@127.0.0.1/thunder -y
