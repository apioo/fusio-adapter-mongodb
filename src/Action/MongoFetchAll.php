<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2016 Christoph Kappestein <k42b3.x@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Fusio\Adapter\Mongodb\Action;

use Fusio\Engine\ActionInterface;
use Fusio\Engine\ConnectorInterface;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\Exception\ConfigurationException;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;
use Fusio\Engine\Response\FactoryInterface as ResponseFactoryInterface;
use Fusio\Engine\Template\FactoryInterface;
use MongoDB;

/**
 * MongoFetchAll
 *
 * @author  Christoph Kappestein <k42b3.x@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0
 * @link    http://fusio-project.org
 */
class MongoFetchAll implements ActionInterface
{
    /**
     * @Inject
     * @var \Fusio\Engine\ConnectorInterface
     */
    protected $connector;

    /**
     * @Inject
     * @var \Fusio\Engine\Template\FactoryInterface
     */
    protected $templateFactory;

    /**
     * @Inject
     * @var \Fusio\Engine\Response\FactoryInterface
     */
    protected $response;

    public function getName()
    {
        return 'Mongo-Fetch-All';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context)
    {
        $connection = $this->connector->getConnection($configuration->get('connection'));
        $collection = $configuration->get('collection');

        // query
        $parser  = $this->templateFactory->newTextParser();
        $query   = $parser->parse($request, $context, $configuration->get('criteria'));
        $query   = !empty($query) ? json_decode($query) : array();

        // projection
        $fields  = $configuration->get('projection');
        $fields  = !empty($fields) ? json_decode($fields) : array();

        // sort
        $sortRaw = $configuration->get('sort');
        $sort    = [];
        if (!empty($sortRaw)) {
            parse_str($sortRaw, $sort);
            $sort = array_map(function ($value) {
                $value = (int) $value;
                return $value === 1 || $value === -1 ? $value : 1;
            }, $sort);
        }

        // limit
        $limit = (int) $configuration->get('limit');
        if ($limit <= 0) {
            $limit = null;
        }

        if ($connection instanceof MongoDB\Database) {
            $data = $this->query($connection, $collection, $query, $fields, $sort, $limit);
        } else {
            throw new ConfigurationException('Given connection must be a MongoDB connection');
        }

        $key = $configuration->get('propertyName') ?: 'entry';

        return $this->response->build(200, [], [
            $key => $data,
        ]);
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory)
    {
        $builder->add($elementFactory->newConnection('connection', 'Connection', 'The MongoDB connection which should be used'));
        $builder->add($elementFactory->newInput('propertyName', 'Property name', 'text', 'The name of the property under which the result should be inserted'));
        $builder->add($elementFactory->newInput('collection', 'Collection', 'text', 'The data gets fetched from this collection'));
        $builder->add($elementFactory->newTextArea('criteria', 'Criteria', 'json', 'Specifies selection criteria using <a href="http://docs.mongodb.org/manual/reference/operator/">query operators</a>. To return all documents in a collection, omit this parameter or pass an empty document ({})'));
        $builder->add($elementFactory->newTextArea('projection', 'Projection', 'json', 'Specifies the fields to return using <a href="http://docs.mongodb.org/manual/reference/operator/projection/">projection operators</a>. To return all fields in the matching document, omit this parameter.'));
        $builder->add($elementFactory->newInput('sort', 'Sort', 'text', 'Sorts the entries after a specific key. I.e. <code>title=1</code> to order after the title ascending or <code>title=-1</code> for descending'));
        $builder->add($elementFactory->newInput('limit', 'Limit', 'text', 'Integer how many entries should be fetched'));
    }

    public function setConnector(ConnectorInterface $connector)
    {
        $this->connector = $connector;
    }

    public function setTemplateFactory(FactoryInterface $templateFactory)
    {
        $this->templateFactory = $templateFactory;
    }

    public function setResponse(ResponseFactoryInterface $response)
    {
        $this->response = $response;
    }

    protected function query(MongoDB\Database $connection, $collection, $query, $fields, $sort, $limit)
    {
        $collection = $connection->selectCollection($collection);
        $options   = [];

        if (!empty($fields)) {
            $options['projection'] = $fields;
        }

        if (!empty($sort)) {
            $options['sort'] = $sort;
        }

        if (!empty($limit)) {
            $options['limit'] = $limit;
        }

        $cursor = $collection->find($query, $options);

        return $cursor->toArray();
    }
}
