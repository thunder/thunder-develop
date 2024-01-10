# Thunder development installation
To install the Thunder Distribution for development create the thunder-develop project:

    composer create-project thunder/thunder-develop -s dev
    cd thunder-develop

This will install thunder into the docroot folder. The actual
distribution repository will be cloned into docroot/profiles/contrib/thunder.

## Ddev Environment
Start the ddev environment for local site install:

    ddev start

Install the site. When composer update was never run, it has to be called twice, because of the composer merge plugin
which is used to merge the distributions dependencies. This is not necesseary when the docroot was build before.:

    ddev composer update
    ddev composer update
    ddev drush si thunder

To work on the distribution, work inside the docroot/profiles/contrib/thunder
folder.

    cd docroot/profiles/contrib/thunder
    git checkout -b feature/new-thunder-feature # <-- this will be a branch in the distribution not the project
    <make changes>
    git commit .


# Run code style tests

To test the code style (Drupal and DrupalPractice) in the distribution run

    ddev composer cs

To test some module run

    ddev composer cs docroot/modules/contrib/mymodule

You can also run phpcbf

    ddev composer cbf

# Testing

Some tests need test fixtures inside the selenium container. To copy the current fixtures run:

    docker cp docroot/profiles/contrib/thunder/tests/fixtures ddev-thunder-develop-selenium-chrome:/fixtures

Run all Thunder tests

    ddev composer exec -- phpunit -c docroot/core docroot/profiles/contrib/thunder

Run single test file (e.g. ArticleSchedulerIntegrationTest.php)

    ddev composer exec -- phpunit -c docroot/core --filter=ArticleSchedulerIntegrationTest docroot/profiles/contrib/thunder

Run single test method (e.g. CacheInvalidationTest::testMetatagsCacheInvalidation)

    ddev composer exec -- phpunit -c docroot/core --filter=testEntityListCacheInvalidation docroot/profiles/contrib/thunder/modules

Run module tests

    ddev composer exec -- phpunit -c docroot/core docroot/modules/contrib/graphql
