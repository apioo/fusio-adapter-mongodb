<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2017 Christoph Kappestein <christoph.kappestein@gmail.com>
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
use PSX\Data\Record\Transformer;
use PSX\Http\Exception as StatusCode;

/**
 * MongoCollection
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0
 * @link    http://fusio-project.org
 */
class MongoCollection extends ActionAbstract
{
    public function getName()
    {
        return 'Mongo-Collection';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context)
    {
        $connection = $this->connector->getConnection($configuration->get('connection'));

        if ($connection instanceof MongoDB\Database) {
            $collection = $connection->selectCollection($configuration->get('collection'));

            switch ($request->getMethod()) {
                case 'GET':
                    return $this->doGet($request, $collection);
                    break;

                case 'POST':
                    return $this->doPost($request, $collection);
                    break;

                case 'PUT':
                    return $this->doPut($request, $collection);
                    break;

                case 'DELETE':
                    return $this->doDelete($request, $collection);
                    break;
            }

            $id = $request->getUriFragment('id');
            if (empty($id)) {
                throw new StatusCode\MethodNotAllowedException('Method not allowed', ['GET', 'POST']);
            } else {
                throw new StatusCode\MethodNotAllowedException('Method not allowed', ['GET', 'PUT', 'DELETE']);
            }
        } else {
            throw new ConfigurationException('Given connection must be a MongoDB connection');
        }
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory)
    {
        $builder->add($elementFactory->newConnection('connection', 'Connection', 'The MongoDB connection which should be used'));
        $builder->add($elementFactory->newInput('collection', 'Collection', 'text', 'Name of the collection'));
    }

    protected function doGet(RequestInterface $request, MongoDB\Collection $collection)
    {
        $id = $request->getUriFragment('id');
        if (empty($id)) {
            return $this->doGetCollection(
                $request,
                $collection
            );
        } else {
            return $this->doGetEntity(
                $id,
                $collection
            );
        }
    }

    protected function doGetCollection(RequestInterface $request, MongoDB\Collection $collection)
    {
        $startIndex  = (int) $request->getParameter('startIndex');
        $count       = (int) $request->getParameter('count');
        $sortBy      = $request->getParameter('sortBy');
        $sortOrder   = $request->getParameter('sortOrder');
        $filterBy    = $request->getParameter('filterBy');
        $filterValue = $request->getParameter('filterValue');

        $startIndex  = $startIndex < 0 ? 0 : $startIndex;
        $count       = $count >= 1 && $count <= 32 ? $count : 16;

        $filter  = [];
        if (!empty($filterBy) && !empty($filterValue)) {
            $filter[$filterBy] = $filterValue;
        }

        $options = [];
        if (!empty($sortBy) && !empty($sortOrder)) {
            $sortOrder = strtoupper($sortOrder);
            $sortOrder = $sortOrder === 'ASC' ? 1 : -1;

            $options['sort'] = [$sortBy => $sortOrder];
        } else {
            $options['sort'] = ['$natural' => -1];
        }

        if (!empty($startIndex)) {
            $options['skip'] = $startIndex;
        }

        if (!empty($count)) {
            $options['limit'] = $count;
        }

        $totalCount = $collection->count($filter);
        $cursor     = $collection->find($filter, $options);
        $result     = $cursor->toArray();

        // transform the mongodb ids to a string
        $len = count($result);
        for ($i = 0; $i < $len; $i++) {
            if (isset($result[$i]['_id'])) {
                $result[$i]['_id'] = (string) $result[$i]['_id'];
            }
        }

        return $this->response->build(200, [], [
            'totalResults' => $totalCount,
            'itemsPerPage' => $count,
            'startIndex'   => $startIndex,
            'entry'        => $result,
        ]);
    }

    protected function doGetEntity($id, MongoDB\Collection $collection)
    {
        $row = $collection->findOne(['_id' => new MongoDB\BSON\ObjectID($id)]);

        if (!empty($row)) {
            // transform the mongodb id to a string
            if (isset($row['_id'])) {
                $row['_id'] = (string) $row['_id'];
            }

            return $this->response->build(200, [], $row);
        } else {
            throw new StatusCode\NotFoundException('Entry not available');
        }
    }

    protected function doPost(RequestInterface $request, MongoDB\Collection $collection)
    {
        $id = $request->getUriFragment('id');
        if (empty($id)) {
            $body   = Transformer::toStdClass($request->getBody());
            $result = $collection->insertOne($body);

            return $this->response->build(201, [], [
                'success' => true,
                'message' => 'Entry successful created',
                'id'      => (string) $result->getInsertedId()
            ]);
        } else {
            throw new StatusCode\MethodNotAllowedException('Method not allowed', ['GET', 'POST']);
        }
    }

    protected function doPut(RequestInterface $request, MongoDB\Collection $collection)
    {
        $id = $request->getUriFragment('id');
        if (!empty($id)) {
            $body = Transformer::toStdClass($request->getBody());

            $collection->updateOne(['_id' => new MongoDB\BSON\ObjectID($id)], ['$set' => $body]);

            return $this->response->build(200, [], [
                'success' => true,
                'message' => 'Entry successful updated'
            ]);
        } else {
            throw new StatusCode\MethodNotAllowedException('Method not allowed', ['GET', 'PUT', 'DELETE']);
        }
    }

    protected function doDelete(RequestInterface $request, MongoDB\Collection $collection)
    {
        $id = $request->getUriFragment('id');
        if (!empty($id)) {
            $collection->deleteOne(['_id' => new MongoDB\BSON\ObjectID($id)]);

            return $this->response->build(200, [], [
                'success' => true,
                'message' => 'Entry successful deleted'
            ]);
        } else {
            throw new StatusCode\MethodNotAllowedException('Method not allowed', ['GET', 'PUT', 'DELETE']);
        }
    }
}
