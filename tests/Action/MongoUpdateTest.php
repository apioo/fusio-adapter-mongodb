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

namespace Fusio\Adapter\Mongodb\Tests\Action;

use Fusio\Adapter\Mongodb\Action\MongoUpdate;
use Fusio\Adapter\Mongodb\Tests\MongoTestCase;
use Fusio\Engine\Form\Builder;
use Fusio\Engine\Form\Container;
use Fusio\Engine\ResponseInterface;
use Fusio\Engine\Test\EngineTestCaseTrait;
use PSX\Record\Record;

/**
 * MongoUpdateTest
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class MongoUpdateTest extends MongoTestCase
{
    use EngineTestCaseTrait;

    public function testHandle()
    {
        $parameters = $this->getParameters([
            'connection'   => 1,
            'propertyName' => 'foo',
            'collection'   => 'app_news',
            'criteria'     => '{"id": {{ request.uriFragment("id")|integer }} }',
            'document'     => '{"$set": {{ request.body|json }} }',
        ]);

        $body = Record::fromArray([
            'id'      => 2,
            'title'   => 'lorem',
            'content' => 'ipsum',
            'date'    => '2015-02-27 19:59:15'
        ]);

        $action   = $this->getActionFactory()->factory(MongoUpdate::class);
        $response = $action->handle($this->getRequest('POST', ['id' => 2], [], [], $body), $parameters, $this->getContext());

        $body = [];
        $body['success'] = true;
        $body['message'] = 'Execution was successful';

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertEquals($body, $response->getBody());

        $row    = $this->connection->selectCollection('app_news')->findOne(['id' => 2]);
        $expect = [
            'id'      => 2,
            'title'   => 'lorem',
            'content' => 'ipsum',
            'date'    => '2015-02-27 19:59:15',
        ];

        unset($row['_id']);

        $this->assertEquals($expect, $row->bsonSerialize());
    }

    public function testGetForm()
    {
        $action  = $this->getActionFactory()->factory(MongoUpdate::class);
        $builder = new Builder();
        $factory = $this->getFormElementFactory();

        $action->configure($builder, $factory);

        $this->assertInstanceOf(Container::class, $builder->getForm());
    }
}
