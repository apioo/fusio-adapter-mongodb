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

namespace Fusio\Adapter\Mongodb\Tests\Action;

use Fusio\Adapter\Mongodb\Action\MongoInsertOne;
use Fusio\Adapter\Mongodb\Tests\MongoTestCase;
use MongoDB\BSON;
use PSX\Http\Environment\HttpResponseInterface;
use PSX\Record\Record;

/**
 * MongoInsertOneTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org/
 */
class MongoInsertOneTest extends MongoTestCase
{
    public function testHandle()
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

        $action   = $this->getActionFactory()->factory(MongoInsertOne::class);
        $response = $action->handle($this->getRequest('POST', [], [], [], $body), $parameters, $this->getContext());

        $row = $this->connection->selectCollection('app_news')->findOne([], ['sort' => ['$natural' => -1], 'limit' => 1]);

        $result = [
            'success' => true,
            'message' => 'Entry successful created',
            'id'      => (string) $row['_id']
        ];

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertEquals($result, $response->getBody());

        // check whether the entry was inserted
        $actual = BSON\toJSON(BSON\fromPHP($row));
        $expect = <<<JSON
{
    "_id": {
        "\$oid" : "{$row['_id']}"
    },
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
}
