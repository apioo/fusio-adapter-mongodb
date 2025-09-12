<?php
/*
 * Fusio - Self-Hosted API Management for Builders.
 * For the current version and information visit <https://www.fusio-project.org/>
 *
 * Copyright (c) Christoph Kappestein <christoph.kappestein@gmail.com>
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

use Fusio\Adapter\Mongodb\Action\MongoDeleteOne;
use Fusio\Adapter\Mongodb\Tests\MongoTestCase;
use PSX\Http\Environment\HttpResponseInterface;

/**
 * MongoDeleteOneTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class MongoDeleteOneTest extends MongoTestCase
{
    public function testHandle()
    {
        $row = $this->connection->selectCollection('app_news')->findOne([], ['sort' => ['$natural' => 1]]);

        $parameters = $this->getParameters([
            'connection' => 1,
            'collection' => 'app_news',
        ]);

        $action   = $this->getActionFactory()->factory(MongoDeleteOne::class);
        $response = $action->handle($this->getRequest('DELETE', ['id' => (string) $row['_id']]), $parameters, $this->getContext());

        $result = [
            'success' => true,
            'message' => 'Document successfully deleted',
        ];

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertEquals($result, $response->getBody());

        // check whether the entry was deleted
        $row = $this->connection->selectCollection('app_news')->findOne(['_id' => $row['_id']]);

        $this->assertEmpty($row);
    }
}
