# Thunder development installation
To install the Thunder Distribution for development, clone this repository and do a composer install in the created 
folder:

    git clone https://github.com/thunder/thunder-develop.git
    cd thunder-develop
    composer install
    
This will install thunder into the docroot folder. The actual 
distribution repository will be cloned into docroot/profiles/contrib/thunder.

To work on the distribution, work inside the docroot/profiles/contrib/thunder
folder. When commiting there, you commit to the distributions repository.

    cd docroot/profiles/contrib/thunder
    git checkout -b feature/new-feature <-- this will be a branch in the distribution
    <make changes>
    git commit .
    
If you need to update your local installation in the docroot folder, to a composer 
install or update in the root folder.

