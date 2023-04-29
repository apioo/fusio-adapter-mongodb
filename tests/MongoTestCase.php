<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2022 Christoph Kappestein <christoph.kappestein@gmail.com>
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

namespace Fusio\Adapter\Mongodb\Tests;

use Fusio\Adapter\Ldap\Connection\Ldap;
use Fusio\Adapter\Mongodb\Action\MongoDeleteOne;
use Fusio\Adapter\Mongodb\Action\MongoFindAll;
use Fusio\Adapter\Mongodb\Action\MongoFindOne;
use Fusio\Adapter\Mongodb\Action\MongoInsertOne;
use Fusio\Adapter\Mongodb\Action\MongoUpdateOne;
use Fusio\Adapter\Mongodb\Connection\MongoDB;
use Fusio\Adapter\Mongodb\Generator\MongoCollection;
use Fusio\Engine\Action\Runtime;
use Fusio\Engine\ConnectorInterface;
use Fusio\Engine\Model\Connection;
use Fusio\Engine\Parameters;
use Fusio\Engine\Test\CallbackConnection;
use Fusio\Engine\Test\EngineTestCaseTrait;
use MongoDB\Database;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * MongoTestCase
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org/
 */
abstract class MongoTestCase extends TestCase
{
    use EngineTestCaseTrait;

    protected ?Database $connection = null;

    protected function configure(Runtime $runtime, Container $container): void
    {
        $container->set(MongoDB::class, new MongoDB());
        $container->set(MongoDeleteOne::class, new MongoDeleteOne($runtime));
        $container->set(MongoFindAll::class, new MongoFindAll($runtime));
        $container->set(MongoFindOne::class, new MongoFindOne($runtime));
        $container->set(MongoInsertOne::class, new MongoInsertOne($runtime));
        $container->set(MongoUpdateOne::class, new MongoUpdateOne($runtime));
        $container->set(MongoCollection::class, new MongoCollection($container->get(ConnectorInterface::class)));
    }

    protected function setUp(): void
    {
        if (!$this->connection) {
            $this->connection = $this->newConnection();
        }

        $connection = new Connection(1, 'foo', CallbackConnection::class, [
            'callback' => function(){
                return $this->connection;
            },
        ]);

        $this->getConnectionRepository()->add($connection);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->connection instanceof Database) {
            $this->connection->dropCollection('app_news');
        }
    }

    protected function newConnection(): Database
    {
        $connector = new MongoDB();

        try {
            $connection = $connector->getConnection(new Parameters([
                'url'      => 'mongodb://root:test1234@127.0.0.1',
                'options'  => '',
                'database' => 'fusio',
            ]));

            try {
                $connection->createCollection('app_news');
            } catch (\MongoDB\Driver\Exception\RuntimeException $e) {
                // collection already exists
                $connection->dropCollection('app_news');
                $connection->createCollection('app_news');
            }

            $data       = $this->getFixtures();
            $collection = $connection->selectCollection('app_news');

            foreach ($data as $row) {
                $collection->insertOne($row);
            }

            return $connection;
        } catch (\Exception $e) {
            $this->markTestSkipped('MongoDB connection not available');
        }
    }
    
    protected function getFixtures(): array
    {
        $result = [];
        $result[] = (object) [
            'title' => 'foo',
            'content' => 'bar',
            'user' => (object) [
                'name' => 'foo',
                'uri' => 'http://google.com'
            ],
            'date' => '2015-02-27 19:59:15',
        ];
        $result[] = (object) [
            'title' => 'bar',
            'content' => 'foo',
            'user' => (object) [
                'name' => 'bar',
                'uri' => 'http://google.com'
            ],
            'date' => '2015-02-27 19:59:15',
        ];

        return $result;
    }
}
