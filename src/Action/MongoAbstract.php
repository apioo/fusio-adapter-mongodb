<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2022 Christoph Kappestein <christoph.kappestein@gmail.com>
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
use Fusio\Engine\Exception\ConfigurationException;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use MongoDB;
use PSX\Http\Exception\BadRequestException;
use PSX\Json\Parser;
use PSX\Record\RecordInterface;

/**
 * MongoAbstract
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0
 * @link    https://www.fusio-project.org/
 */
abstract class MongoAbstract extends ActionAbstract
{
    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newConnection('connection', 'Connection', 'The MongoDB connection which should be used'));
        $builder->add($elementFactory->newInput('collection', 'Collection', 'text', 'Name of the collection'));
    }

    protected function getConnection(ParametersInterface $configuration): MongoDB\Database
    {
        $connection = $this->connector->getConnection($configuration->get('connection'));
        if (!$connection instanceof MongoDB\Database) {
            throw new ConfigurationException('Given connection must be a MongoDB connection');
        }

        return $connection;
    }

    protected function getCollection(ParametersInterface $configuration): string
    {
        $collection = $configuration->get('collection');
        if (empty($collection)) {
            throw new ConfigurationException('No collection name provided');
        }

        return $collection;
    }

    protected function toStdClass(mixed $body): object
    {
        if ($body instanceof \stdClass) {
            return $body;
        } elseif (is_array($body)) {
            return (object) $body;
        } elseif ($body instanceof RecordInterface) {
            return Parser::decodeAsObject(Parser::encode($body));
        } else {
            throw new BadRequestException('Provided an invalid request body');
        }
    }
}
