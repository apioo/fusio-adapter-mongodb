<?php

use Fusio\Adapter\Mongodb\Action\MongoDeleteOne;
use Fusio\Adapter\Mongodb\Action\MongoFindAll;
use Fusio\Adapter\Mongodb\Action\MongoFindOne;
use Fusio\Adapter\Mongodb\Action\MongoInsertOne;
use Fusio\Adapter\Mongodb\Action\MongoUpdateOne;
use Fusio\Adapter\Mongodb\Connection\MongoDB;
use Fusio\Adapter\Mongodb\Generator\MongoCollection;
use Fusio\Engine\Adapter\ServiceBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container) {
    $services = ServiceBuilder::build($container);
    $services->set(MongoDB::class);
    $services->set(MongoDeleteOne::class);
    $services->set(MongoFindAll::class);
    $services->set(MongoFindOne::class);
    $services->set(MongoInsertOne::class);
    $services->set(MongoUpdateOne::class);
    $services->set(MongoCollection::class);
};
