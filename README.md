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
    

# Install development environment
Install lando and its requirements: https://docs.devwithlando.io/
then call:

    lando start

This will install appropriate docker containers. Now you can access your installation at http://thunder.lndo.site/
To use composer within the container call:

    lando composer 

To use drush call:

    lando drush 
    
You can use drush to install thunder:

    lando drush si thunder --db-url=mysql://drupal8:drupal8@database/drupal8 
    
By default, xdebug is disabled, you can change that in tha .lando.yml file by setting "xdebug: false".
The .lando.yml file can be used to customize your environment, see: https://docs.devwithlando.io/tutorials/drupal8.html
