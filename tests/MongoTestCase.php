<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2016 Christoph Kappestein <k42b3.x@gmail.com>
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

use Fusio\Adapter\Mongodb\Connection\MongoDB;
use Fusio\Engine\Model\Connection;
use Fusio\Engine\Parameters;
use Fusio\Engine\Test\CallbackConnection;
use Fusio\Engine\Test\EngineTestCaseTrait;

/**
 * MongoTestCase
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
abstract class MongoTestCase extends \PHPUnit_Framework_TestCase
{
    use EngineTestCaseTrait;

    protected static $hasConnection = true;

    /**
     * @var \MongoDB\Database
     */
    protected $connection;

    protected function setUp()
    {
        if (!self::$hasConnection) {
            $this->markTestSkipped('MongoDB connection not available');
        }

        if (!$this->connection) {
            $this->connection = $this->newConnection();
        }

        $connection = new Connection();
        $connection->setId(1);
        $connection->setName('foo');
        $connection->setClass(CallbackConnection::class);
        $connection->setConfig([
            'callback' => function(){
                return $this->connection;
            },
        ]);

        $this->getConnectionRepository()->add($connection);
    }

    protected function tearDown()
    {
        parent::tearDown();

        if (self::$hasConnection) {
            if ($this->connection instanceof \MongoDB\Database) {
                $this->connection->dropCollection('app_news');
            }
        }
    }

    protected function newConnection()
    {
        $connector = new MongoDB();

        try {
            $connection = $connector->getConnection(new Parameters([
                'url'      => 'mongodb://127.0.0.1',
                'options'  => '',
                'database' => 'test',
            ]));

            $connection->createCollection('app_news');

            $data       = $this->getFixtures();
            $collection = $connection->selectCollection('app_news');

            foreach ($data as $row) {
                $collection->insertOne($row);
            }

            return $connection;
        } catch (\Exception $e) {
            self::$hasConnection = false;

            $this->markTestSkipped('MongoDB connection not available');
        }

        return null;
    }
    
    protected function getFixtures()
    {
        $result = [];
        $result[] = [
            'id' => 1,
            'title' => 'foo',
            'content' => 'bar',
            'date' => '2015-02-27 19:59:15',
        ];
        $result[] = [
            'id' => 2,
            'title' => 'bar',
            'content' => 'foo',
            'date' => '2015-02-27 19:59:15',
        ];

        return $result;
    }
}
