<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2020 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Fusio\Adapter\Mongodb\Routes;

use Fusio\Adapter\Mongodb\Action\MongoDeleteOne;
use Fusio\Adapter\Mongodb\Action\MongoFindAll;
use Fusio\Adapter\Mongodb\Action\MongoFindOne;
use Fusio\Adapter\Mongodb\Action\MongoInsertOne;
use Fusio\Adapter\Mongodb\Action\MongoUpdateOne;
use Fusio\Engine\ConnectorInterface;
use Fusio\Engine\Factory\Resolver\PhpClass;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\Routes\ProviderInterface;
use Fusio\Engine\Routes\SetupInterface;

/**
 * MongoCollection
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org/
 */
class MongoCollection implements ProviderInterface
{
    /**
     * @var ConnectorInterface
     */
    private $connector;

    /**
     * @var SchemaBuilder
     */
    private $schemaBuilder;

    public function __construct(ConnectorInterface $connector)
    {
        $this->connector = $connector;
        $this->schemaBuilder = new SchemaBuilder();
    }

    public function getName()
    {
        return 'Mongo-Collection';
    }

    public function setup(SetupInterface $setup, string $basePath, ParametersInterface $configuration)
    {
        $prefix = $this->getPrefix($basePath);
        $schemaParameters = $setup->addSchema('Mongo-Collection-Parameters', $this->schemaBuilder->getParameters());

        $fetchAllAction = $setup->addAction($prefix . '-Find-All', MongoFindAll::class, PhpClass::class, [
            'connection' => $configuration->get('connection'),
            'collection' => $configuration->get('collection'),
        ]);

        $fetchRowAction = $setup->addAction($prefix . '-Find-Row', MongoFindOne::class, PhpClass::class, [
            'connection' => $configuration->get('connection'),
            'collection' => $configuration->get('collection'),
        ]);

        $deleteAction = $setup->addAction($prefix . '-Delete', MongoDeleteOne::class, PhpClass::class, [
            'connection' => $configuration->get('connection'),
            'collection' => $configuration->get('collection'),
        ]);

        $insertAction = $setup->addAction($prefix . '-Insert', MongoInsertOne::class, PhpClass::class, [
            'connection' => $configuration->get('connection'),
            'collection' => $configuration->get('collection'),
        ]);

        $updateAction = $setup->addAction($prefix . '-Update', MongoUpdateOne::class, PhpClass::class, [
            'connection' => $configuration->get('connection'),
            'collection' => $configuration->get('collection'),
        ]);

        $setup->addRoute(1, '/', 'Fusio\Impl\Controller\SchemaApiController', [], [
            [
                'version' => 1,
                'methods' => [
                    'GET' => [
                        'active' => true,
                        'public' => true,
                        'description' => 'Returns a collection of entities',
                        'parameters' => $schemaParameters,
                        'responses' => [
                            200 => -1,
                        ],
                        'action' => $fetchAllAction,
                    ],
                    'POST' => [
                        'active' => true,
                        'public' => false,
                        'description' => 'Creates a new entity',
                        'request' => -1,
                        'responses' => [
                            201 => -1,
                        ],
                        'action' => $insertAction,
                    ]
                ],
            ]
        ]);

        $setup->addRoute(1, '/:id', 'Fusio\Impl\Controller\SchemaApiController', [], [
            [
                'version' => 1,
                'methods' => [
                    'GET' => [
                        'active' => true,
                        'public' => true,
                        'description' => 'Returns a single entity',
                        'responses' => [
                            200 => -1,
                        ],
                        'action' => $fetchRowAction,
                    ],
                    'PUT' => [
                        'active' => true,
                        'public' => false,
                        'description' => 'Updates an existing entity',
                        'request' => -1,
                        'responses' => [
                            200 => -1,
                        ],
                        'action' => $updateAction,
                    ],
                    'DELETE' => [
                        'active' => true,
                        'public' => false,
                        'description' => 'Deletes an existing entity',
                        'responses' => [
                            200 => -1,
                        ],
                        'action' => $deleteAction,
                    ]
                ],
            ]
        ]);
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory)
    {
        $builder->add($elementFactory->newConnection('connection', 'Connection', 'The mongo connection which should be used'));
        $builder->add($elementFactory->newInput('collection', 'Collection', 'text', 'Name of the collection'));
    }

    private function getPrefix(string $path)
    {
        return implode('-', array_map('ucfirst', array_filter(explode('/', $path))));
    }
}
