# Thunder development installation
To install the Thunder Distribution for development create the thunder-develop project:

    composer create-project thunder/thunder-develop -s dev
    cd thunder-develop

This will install thunder into the docroot folder. The actual
distribution repository will be cloned into docroot/profiles/contrib/thunder.

If the docroot folder does not contain the index.php execute the drupal-scaffold composer command

    composer drupal-scaffold

Now you can install thunder. Point the web server to the docroot directory and do a normal site install.

To work on the distribution, work inside the docroot/profiles/contrib/thunder
folder.

    cd docroot/profiles/contrib/thunder
    git checkout -b feature/new-thunder-feature # <-- this will be a branch in the distribution not the project
    <make changes>
    git commit .

# Run code style tests

To test the code style (Drupal and DrupalPractice) in the distribution run

    composer cs

To test some module run

    composer cs docroot/modules/contrib/select2

You can also run phpcbf

    composer cbf

# Testing

Create test dump file

    ddev exec -d /var/www/html/docroot php core/scripts/db-tools.php dump-database-d8-mysql > docroot/test-database-dump.php

Run all Thunder tests

    ddev composer exec -- phpunit -c docroot/core docroot/profiles/contrib/thunder

Run single test file (e.g. CacheInvalidationTest.php)

    ddev composer exec -- phpunit -c docroot/core --filter=CacheInvalidationTest docroot/profiles/contrib/thunder

Run single test method (e.g. CacheInvalidationTest::testMetatagsCacheInvalidation)

    ddev composer exec -- phpunit -c docroot/core --filter=testEntityListCacheInvalidation docroot/profiles/contrib/thunder/modules

Run module tests

    ddev composer exec -- phpunit -c docroot/core docroot/modules/contrib/graphql


ddev exec -d /var/www/html/docroot "../vendor/bin/phpunit -v -c ./core/phpunit.xml.dist ./core/modules/system/tests/src/FunctionalJavascript/FrameworkTest.php"
