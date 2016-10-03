Fusio-Adapter-MongoDB
=====

[Fusio] adapter which provides actions and connections to work with a mongo 
database. The adapter uses the `mongodb/mongodb` package thus it requires the 
`mongod` extension which only works with PHP 7. You can install the adapter with 
the following steps inside your Fusio project:

    composer require fusio/adapter-mongodb
    php bin/fusio system:register Fusio\Adapter\Mongodb\Adapter

More informations about Fusio at http://fusio-project.org

[Fusio]: http://demo.fusio-project.org/