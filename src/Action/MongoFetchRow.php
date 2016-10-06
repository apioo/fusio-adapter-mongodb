<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2016 Christoph Kappestein <christoph.kappestein@gmail.com>
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

use Fusio\Engine\ActionAbstract;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\Exception\ConfigurationException;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;
use MongoDB;
use PSX\Http\Exception as StatusCode;

/**
 * MongoFetchRow
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0
 * @link    http://fusio-project.org
 */
class MongoFetchRow extends ActionAbstract
{
    public function getName()
    {
        return 'Mongo-Fetch-Row';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context)
    {
        $connection = $this->connector->getConnection($configuration->get('connection'));

        // parse json
        $parser = $this->templateFactory->newTextParser();
        $query  = $parser->parse($request, $context, $configuration->get('criteria'));
        $query  = !empty($query) ? json_decode($query) : array();

        $fields = $configuration->get('projection');
        $fields = !empty($fields) ? json_decode($fields) : array();

        if ($connection instanceof MongoDB\Database) {
            $collection = $connection->selectCollection($configuration->get('collection'));

            if ($collection instanceof MongoDB\Collection) {
                $result = $collection->findOne($query, ['projection' => $fields]);
            } else {
                throw new ConfigurationException('Invalid collection');
            }
        } else {
            throw new ConfigurationException('Given connection must be a MongoDB connection');
        }

        if (empty($result)) {
            throw new StatusCode\NotFoundException('Entry not available');
        }

        return $this->response->build(200, [], $result);
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory)
    {
        $builder->add($elementFactory->newConnection('connection', 'Connection', 'The MongoDB connection which should be used'));
        $builder->add($elementFactory->newInput('collection', 'Collection', 'text', 'The data gets fetched from this collection'));
        $builder->add($elementFactory->newTextArea('criteria', 'Criteria', 'json', 'Specifies selection criteria using <a href="http://docs.mongodb.org/manual/reference/operator/">query operators</a>. To return all documents in a collection, omit this parameter or pass an empty document ({})'));
        $builder->add($elementFactory->newTextArea('projection', 'Projection', 'json', 'Specifies the fields to return using <a href="http://docs.mongodb.org/manual/reference/operator/projection/">projection operators</a>. To return all fields in the matching document, omit this parameter.'));
    }
}
