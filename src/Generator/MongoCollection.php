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

namespace Fusio\Adapter\Mongodb\Generator;

use Fusio\Adapter\Mongodb\Action\MongoDeleteOne;
use Fusio\Adapter\Mongodb\Action\MongoFindAll;
use Fusio\Adapter\Mongodb\Action\MongoFindOne;
use Fusio\Adapter\Mongodb\Action\MongoInsertOne;
use Fusio\Adapter\Mongodb\Action\MongoUpdateOne;
use Fusio\Engine\Factory\Resolver\PhpClass;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\Generator\ProviderInterface;
use Fusio\Engine\Generator\SetupInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\Schema\SchemaBuilder;
use Fusio\Engine\Schema\SchemaName;
use Fusio\Model\Backend\ActionConfig;
use Fusio\Model\Backend\ActionCreate;
use Fusio\Model\Backend\OperationCreate;
use Fusio\Model\Backend\SchemaCreate;

/**
 * MongoCollection
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org/
 */
class MongoCollection implements ProviderInterface
{
    private const SCHEMA_GET_ALL = 'MongoDB_GetAll';
    private const ACTION_GET_ALL = 'MongoDB_GetAll';
    private const ACTION_GET = 'MongoDB_Get';
    private const ACTION_INSERT = 'MongoDB_Insert';
    private const ACTION_UPDATE = 'MongoDB_Update';
    private const ACTION_DELETE = 'MongoDB_Delete';

    public function getName(): string
    {
        return 'MongoDB-Collection';
    }

    public function setup(SetupInterface $setup, ParametersInterface $configuration): void
    {
        $setup->addSchema($this->makeGetAllSchema());

        $setup->addAction($this->makeGetAllAction($configuration));
        $setup->addAction($this->makeGetAction($configuration));
        $setup->addAction($this->makeInsertAction($configuration));
        $setup->addAction($this->makeUpdateAction($configuration));
        $setup->addAction($this->makeDeleteAction($configuration));

        $setup->addOperation($this->makeGetAllOperation());
        $setup->addOperation($this->makeGetOperation());
        $setup->addOperation($this->makeInsertOperation());
        $setup->addOperation($this->makeUpdateOperation());
        $setup->addOperation($this->makeDeleteOperation());
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newConnection('connection', 'Connection', 'The mongo connection which should be used'));
        $builder->add($elementFactory->newInput('collection', 'Collection', 'text', 'Name of the collection'));
    }

    private function makeGetAllSchema(): SchemaCreate
    {
        $schema = new SchemaCreate();
        $schema->setName(self::SCHEMA_GET_ALL);
        $schema->setSource(SchemaBuilder::makeCollectionResponse(self::SCHEMA_GET_ALL, null));
        return $schema;
    }

    private function makeGetAllAction(ParametersInterface $configuration): ActionCreate
    {
        $action = new ActionCreate();
        $action->setName(self::ACTION_GET_ALL);
        $action->setClass(MongoFindAll::class);
        $action->setEngine(PhpClass::class);
        $action->setConfig(ActionConfig::fromArray([
            'connection' => $configuration->get('connection'),
            'collection' => $configuration->get('collection'),
        ]));
        return $action;
    }

    private function makeGetAction(ParametersInterface $configuration): ActionCreate
    {
        $action = new ActionCreate();
        $action->setName(self::ACTION_GET);
        $action->setClass(MongoFindOne::class);
        $action->setEngine(PhpClass::class);
        $action->setConfig(ActionConfig::fromArray([
            'connection' => $configuration->get('connection'),
            'collection' => $configuration->get('collection'),
        ]));
        return $action;
    }

    private function makeInsertAction(ParametersInterface $configuration): ActionCreate
    {
        $action = new ActionCreate();
        $action->setName(self::ACTION_INSERT);
        $action->setClass(MongoInsertOne::class);
        $action->setEngine(PhpClass::class);
        $action->setConfig(ActionConfig::fromArray([
            'connection' => $configuration->get('connection'),
            'collection' => $configuration->get('collection'),
        ]));
        return $action;
    }

    private function makeUpdateAction(ParametersInterface $configuration): ActionCreate
    {
        $action = new ActionCreate();
        $action->setName(self::ACTION_UPDATE);
        $action->setClass(MongoUpdateOne::class);
        $action->setEngine(PhpClass::class);
        $action->setConfig(ActionConfig::fromArray([
            'connection' => $configuration->get('connection'),
            'collection' => $configuration->get('collection'),
        ]));
        return $action;
    }

    private function makeDeleteAction(ParametersInterface $configuration): ActionCreate
    {
        $action = new ActionCreate();
        $action->setName(self::ACTION_DELETE);
        $action->setClass(MongoDeleteOne::class);
        $action->setEngine(PhpClass::class);
        $action->setConfig(ActionConfig::fromArray([
            'connection' => $configuration->get('connection'),
            'collection' => $configuration->get('collection'),
        ]));
        return $action;
    }

    private function makeGetAllOperation(): OperationCreate
    {
        $operation = new OperationCreate();
        $operation->setName('getAll');
        $operation->setDescription('Returns a collection of documents');
        $operation->setHttpMethod('GET');
        $operation->setHttpPath('/');
        $operation->setHttpCode(200);
        $operation->setParameters(SchemaBuilder::makeCollectionParameters());
        $operation->setOutgoing(self::SCHEMA_GET_ALL);
        $operation->setAction(self::ACTION_GET_ALL);
        return $operation;
    }

    private function makeGetOperation(): OperationCreate
    {
        $operation = new OperationCreate();
        $operation->setName('get');
        $operation->setDescription('Returns a single document');
        $operation->setHttpMethod('GET');
        $operation->setHttpPath('/:id');
        $operation->setHttpCode(200);
        $operation->setOutgoing(SchemaName::PASSTHRU);
        $operation->setAction(self::ACTION_GET);
        return $operation;
    }

    private function makeInsertOperation(): OperationCreate
    {
        $operation = new OperationCreate();
        $operation->setName('insert');
        $operation->setDescription('Creates a new document');
        $operation->setHttpMethod('POST');
        $operation->setHttpPath('/');
        $operation->setHttpCode(200);
        $operation->setIncoming(SchemaName::PASSTHRU);
        $operation->setOutgoing(SchemaName::MESSAGE);
        $operation->setAction(self::ACTION_INSERT);
        return $operation;
    }

    private function makeUpdateOperation(): OperationCreate
    {
        $operation = new OperationCreate();
        $operation->setName('update');
        $operation->setDescription('Updates an existing document');
        $operation->setHttpMethod('PUT');
        $operation->setHttpPath('/:id');
        $operation->setHttpCode(200);
        $operation->setIncoming(SchemaName::PASSTHRU);
        $operation->setOutgoing(SchemaName::MESSAGE);
        $operation->setAction(self::ACTION_UPDATE);
        return $operation;
    }

    private function makeDeleteOperation(): OperationCreate
    {
        $operation = new OperationCreate();
        $operation->setName('delete');
        $operation->setDescription('Deletes an existing document');
        $operation->setHttpMethod('DELETE');
        $operation->setHttpPath('/:id');
        $operation->setHttpCode(200);
        $operation->setOutgoing(SchemaName::MESSAGE);
        $operation->setAction(self::ACTION_DELETE);
        return $operation;
    }
}
