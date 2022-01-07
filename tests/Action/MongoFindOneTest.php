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

use Fusio\Adapter\Mongodb\Action\MongoFindOne;
use Fusio\Adapter\Mongodb\Tests\MongoTestCase;
use PSX\Http\Environment\HttpResponseInterface;

/**
 * MongoFindOneTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org/
 */
class MongoFindOneTest extends MongoTestCase
{
    public function testHandle()
    {
        $row = $this->connection->selectCollection('app_news')->findOne([], ['sort' => ['$natural' => 1]]);

        $parameters = $this->getParameters([
            'connection' => 1,
            'collection' => 'app_news',
        ]);

        $action   = $this->getActionFactory()->factory(MongoFindOne::class);
        $response = $action->handle($this->getRequest('GET', ['id' => $row->_id]), $parameters, $this->getContext());

        $data   = $response->getBody();
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
}
