<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2018 Christoph Kappestein <christoph.kappestein@gmail.com>
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

use Fusio\Engine\ContextInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;

/**
 * MongoFindAll
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0
 * @link    http://fusio-project.org
 */
class MongoFindAll extends MongoAbstract
{
    public function getName()
    {
        return 'Mongo-Find-All';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context)
    {
        $connection = $this->getConnection($configuration);
        $collection = $connection->selectCollection($this->getCollection($configuration));

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

        $totalCount = $collection->countDocuments($filter);
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
}
