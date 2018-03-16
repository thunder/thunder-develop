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

### Running tests for Thunder Distribution with `ddev`

To run tests for distribution Selenium docker container is required. That's provided with `docker-compose.selenium.yaml`.
Tests has to be executed inside `web` container, that's why it's required to ssh inside with `ddev ssh`.
After that global environment variable should be set:
```export THUNDER_WEBDRIVER_HOST="selenium:4444"```
with that environment for executing tests is set.

Tests can be run like this: `php core/scripts/run-tests.sh --url http://thunder.ddev.local --verbose Thunder`
