Fusio-Adapter-MongoDB
=====

[Fusio] adapter which provides a connection to work with a MongoDB. The adapter uses the `mongodb/mongodb` package thus
it requires the `mongod` extension. You can install the adapter with the following steps inside your Fusio project:

    composer require fusio/adapter-mongodb
    php bin/fusio system:register "Fusio\Adapter\Mongodb\Adapter"

[Fusio]: https://www.fusio-project.org/
