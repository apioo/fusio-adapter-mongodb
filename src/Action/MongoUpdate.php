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

/**
 * MongoUpdate
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0
 * @link    http://fusio-project.org
 */
class MongoUpdate extends ActionAbstract
{
    public function getName()
    {
        return 'Mongo-Update';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context)
    {
        $connection = $this->connector->getConnection($configuration->get('connection'));

        // parse json
        $parser   = $this->templateFactory->newTextParser(__LINE__);
        $criteria = $parser->parse($request, $context, $configuration->get('criteria'));
        $criteria = !empty($criteria) ? json_decode($criteria) : array();

        $parser   = $this->templateFactory->newTextParser(__LINE__);
        $document = $parser->parse($request, $context, $configuration->get('document'));
        $document = !empty($document) ? json_decode($document) : array();

        if ($connection instanceof MongoDB\Database) {
            $collection = $connection->selectCollection($configuration->get('collection'));

            if ($collection instanceof MongoDB\Collection) {
                $collection->updateOne($criteria, $document);
            } else {
                throw new ConfigurationException('Invalid collection');
            }
        } else {
            throw new ConfigurationException('Given connection must be a MongoDB connection');
        }

        return $this->response->build(200, [], [
            'success' => true,
            'message' => 'Execution was successful'
        ]);
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory)
    {
        $builder->add($elementFactory->newConnection('connection', 'Connection', 'The MongoDB connection which should be used'));
        $builder->add($elementFactory->newInput('collection', 'Collection', 'text', 'Inserts the document into this collection'));
        $builder->add($elementFactory->newTextArea('criteria', 'Criteria', 'json', 'Query criteria for the documents to update'));
        $builder->add($elementFactory->newTextArea('document', 'Document', 'json', 'The object used to update the matched documents. It is possible to access values from the environment with i.e. <code ng-non-bindable>{{ request.body|json }}</code>. Click <a ng-click="help.showDialog(\'help/template.md\')">here</a> for more informations about the template syntax.'));
    }
}
