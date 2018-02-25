<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2018 Christoph Kappestein <christoph.kappestein@gmail.com>
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

namespace Fusio\Adapter\Mongodb\Tests\Action;

use Fusio\Adapter\Mongodb\Action\MongoCollection;
use Fusio\Adapter\Mongodb\Tests\MongoTestCase;
use Fusio\Engine\Form\Builder;
use Fusio\Engine\Form\Container;
use MongoDB\BSON\ObjectID;
use PSX\Http\Environment\HttpResponseInterface;
use PSX\Record\Record;
use PSX\Record\Transformer;

/**
 * MongoCollectionTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class MongoCollectionTest extends MongoTestCase
{
    public function testHandleGetCollection()
    {
        $parameters = $this->getParameters([
            'connection' => 1,
            'collection' => 'app_news',
        ]);

        $action   = $this->getActionFactory()->factory(MongoCollection::class);
        $response = $action->handle($this->getRequest(), $parameters, $this->getContext());

        $data = Transformer::toObject($response->getBody());

        $rows = $this->connection->selectCollection('app_news')->find();
        $ids  = [];
        foreach ($rows as $row) {
            $ids[] = $row->_id;
        }

        $actual = json_encode($data, JSON_PRETTY_PRINT);
        $expect = <<<JSON
{
    "totalResults": 2,
    "itemsPerPage": 16,
    "startIndex": 0,
    "entry": [
        {
            "_id": "{$ids[1]}",
            "title": "bar",
            "content": "foo",
            "user": {
                "name": "bar",
                "uri": "http:\/\/google.com"
            },
            "date": "2015-02-27 19:59:15"
        },
        {
            "_id": "{$ids[0]}",
            "title": "foo",
            "content": "bar",
            "user": {
                "name": "foo",
                "uri": "http:\/\/google.com"
            },
            "date": "2015-02-27 19:59:15"
        }
    ]
}
JSON;

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertJsonStringEqualsJsonString($expect, $actual, $actual);
    }

    public function testHandleGetEntity()
    {
        $row = $this->connection->selectCollection('app_news')->findOne([], ['sort' => ['$natural' => 1]]);

        $parameters = $this->getParameters([
            'connection' => 1,
            'collection' => 'app_news',
        ]);

        $action   = $this->getActionFactory()->factory(MongoCollection::class);
        $response = $action->handle($this->getRequest('GET', ['id' => $row->_id]), $parameters, $this->getContext());

        $data = Transformer::toObject($response->getBody());

        $actual = json_encode($data, JSON_PRETTY_PRINT);
        $expect = <<<JSON
{
    "_id": "{$row->_id}",
    "title": "foo",
    "content": "bar",
    "user": {
        "name": "foo",
        "uri": "http:\/\/google.com"
    },
    "date": "2015-02-27 19:59:15"
}
JSON;

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertJsonStringEqualsJsonString($expect, $actual, $actual);
    }

    public function testHandlePost()
    {
        $parameters = $this->getParameters([
            'connection' => 1,
            'collection' => 'app_news',
        ]);

        $body = Record::fromArray([
            'title' => 'lorem',
            'content' => 'ipsum',
            'user' => Record::fromArray([
                'name' => 'lorem',
                'uri' => 'http://google.com'
            ]),
            'date' => '2015-02-27 19:59:15',
        ]);

        $action   = $this->getActionFactory()->factory(MongoCollection::class);
        $response = $action->handle($this->getRequest('POST', [], [], [], $body), $parameters, $this->getContext());

        $row = $this->connection->selectCollection('app_news')->findOne([], ['sort' => ['$natural' => -1], 'limit' => 1]);

        // transform the mongodb id to a string
        if (isset($row['_id'])) {
            $row['_id'] = (string) $row['_id'];
        }

        $data = Transformer::toObject($row);

        $result = [
            'success' => true,
            'message' => 'Entry successful created',
            'id'      => $data->_id
        ];

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertEquals($result, $response->getBody());

        // check whether the entry was inserted
        $actual = json_encode($data, JSON_PRETTY_PRINT);
        $expect = <<<JSON
{
    "_id": "{$data->_id}",
    "title": "lorem",
    "content": "ipsum",
    "user": {
        "name": "lorem",
        "uri": "http:\/\/google.com"
    },
    "date": "2015-02-27 19:59:15"
}
JSON;

        $this->assertJsonStringEqualsJsonString($expect, $actual, $actual);
    }

    public function testHandlePut()
    {
        $row = $this->connection->selectCollection('app_news')->findOne([], ['sort' => ['$natural' => 1]]);

        // transform the mongodb id to a string
        if (isset($row['_id'])) {
            $row['_id'] = (string) $row['_id'];
        }

        $data = Transformer::toObject($row);

        $parameters = $this->getParameters([
            'connection' => 1,
            'collection' => 'app_news',
        ]);

        $body = Record::fromArray([
            'title' => 'lorem',
            'content' => 'ipsum',
            'user' => Record::fromArray([
                'name' => 'lorem',
                'uri' => 'http://google.com'
            ]),
            'date' => '2015-02-27 19:59:15',
        ]);

        $action   = $this->getActionFactory()->factory(MongoCollection::class);
        $response = $action->handle($this->getRequest('PUT', ['id' => $data->_id], [], [], $body), $parameters, $this->getContext());

        $result = [
            'success' => true,
            'message' => 'Entry successful updated',
        ];

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertEquals($result, $response->getBody());

        // check whether the entry was updated
        $row = $this->connection->selectCollection('app_news')->findOne(['_id' => new ObjectID($data->_id)]);

        // transform the mongodb id to a string
        if (isset($row['_id'])) {
            $row['_id'] = (string) $row['_id'];
        }

        $data   = Transformer::toObject($row);
        $actual = json_encode($data, JSON_PRETTY_PRINT);
        $expect = <<<JSON
{
    "_id": "{$data->_id}",
    "title": "lorem",
    "content": "ipsum",
    "user": {
        "name": "lorem",
        "uri": "http:\/\/google.com"
    },
    "date": "2015-02-27 19:59:15"
}
JSON;

        $this->assertJsonStringEqualsJsonString($expect, $actual, $actual);
    }

    public function testHandleDelete()
    {
        $row = $this->connection->selectCollection('app_news')->findOne([], ['sort' => ['$natural' => 1]]);

        // transform the mongodb id to a string
        if (isset($row['_id'])) {
            $row['_id'] = (string) $row['_id'];
        }

        $data = Transformer::toObject($row);

        $parameters = $this->getParameters([
            'connection' => 1,
            'collection' => 'app_news',
        ]);

        $action   = $this->getActionFactory()->factory(MongoCollection::class);
        $response = $action->handle($this->getRequest('DELETE', ['id' => $data->_id]), $parameters, $this->getContext());

        $result = [
            'success' => true,
            'message' => 'Entry successful deleted',
        ];

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertEquals($result, $response->getBody());

        // check whether the entry was deleted
        $row = $this->connection->selectCollection('app_news')->findOne(['_id' => new ObjectID($data->_id)]);

        $this->assertEmpty($row);
    }

    public function testGetForm()
    {
        $action  = $this->getActionFactory()->factory(MongoCollection::class);
        $builder = new Builder();
        $factory = $this->getFormElementFactory();

        $action->configure($builder, $factory);

        $this->assertInstanceOf(Container::class, $builder->getForm());
    }
}
