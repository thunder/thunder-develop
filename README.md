# Thunder development installation
To install the Thunder Distribution for development create the thunder-develop project:

    composer create-project thunder/thunder-develop -s dev
    cd thunder-develop
    
This will install thunder into the docroot folder. The actual 
distribution repository will be cloned into docroot/profiles/contrib/thunder.

If the docroot folder does not contain the index.php execute the drupal-scaffold composer command

    composer drupal-scaffold
    
We recommend to use ddev for local development (see https://github.com/drud/ddev for more information and installation). 
After installing ddev you can do: 

    ddev start

in the project root folder. Now you can access your site in a browser by going to http://thunder-develop.ddev.local/
and do a site install.
    
To use drush you can do:

    ddev exec drush <command>
    
To enter the docker environment do:

    ddev ssh


To work on the distribution, work inside the docroot/profiles/contrib/thunder
folder. 

    cd docroot/profiles/contrib/thunder
    git checkout -b feature/new-thunder-feature # <-- this will be a branch in the distribution not the project
    <make changes>
    git commit .
