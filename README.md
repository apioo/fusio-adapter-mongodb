Fusio-Adapter-MongoDB
=====

[Fusio] adapter which provides a connection to work with a MongoDB. The adapter 
uses the `mongodb/mongodb` package thus it requires the `mongod` extension which 
only works with PHP 7. You can install the adapter with the following steps 
inside your Fusio project:

    composer require fusio/adapter-mongodb
    php bin/fusio system:register Fusio\Adapter\Mongodb\Adapter

[Fusio]: http://fusio-project.org/
