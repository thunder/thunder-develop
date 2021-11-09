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

To use the provided docker compose setup copy .env.example to .env and change the file settings to your liking. To
prevent user access problems to created files and folders make sure to use your user id (call id --user) and group id
(id --group) for DOCKER_USER_ID and DOCKER_GROUP_ID.
To spin up the environment run

    docker-compose up -d

# Run code style tests

To test the code style (Drupal and DrupalPractice) in the distribution run

    composer cs

To test some module run

    composer cs docroot/modules/contrib/select2

You can also run phpcbf

    composer cbf
