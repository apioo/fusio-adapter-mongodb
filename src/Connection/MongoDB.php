<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2016 Christoph Kappestein <christoph.kappestein@gmail.com>
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

namespace Fusio\Adapter\Mongodb\Connection;

use Fusio\Engine\ConnectionInterface;
use Fusio\Engine\Exception\ConfigurationException;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use MongoDB\Client;

/**
 * MongoDB
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class MongoDB implements ConnectionInterface
{
    /**
     * @var \MongoDB\Client
     */
    private static $client;

    public function getName()
    {
        return 'Mongo-DB';
    }

    /**
     * @param \Fusio\Engine\ParametersInterface $config
     * @return \MongoDB\Database
     */
    public function getConnection(ParametersInterface $config)
    {
        $database   = $config->get('database');
        $options    = [];
        $rawOptions = $config->get('options');
        if (!empty($rawOptions)) {
            parse_str($rawOptions, $options);
        }

        if (self::$client) {
            return self::$client->selectDatabase($database);
        }

        if (class_exists('MongoDB\Client')) {
            self::$client = new Client($config->get('url'), $options);

            return self::$client->selectDatabase($database);
        } else {
            throw new ConfigurationException('PHP extension "mongod" is not installed');
        }
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory)
    {
        $builder->add($elementFactory->newInput('url', 'Url', 'text', 'The connection string for the database i.e. <code>mongodb://localhost:27017</code>. Click <a ng-click="help.showDialog(\'help/connection/mongodb.md\')">here</a> for more informations.'));
        $builder->add($elementFactory->newInput('options', 'Options', 'text', 'Optional options for the connection. Click <a ng-click="help.showDialog(\'help/connection/mongodb.md\')">here</a> for more informations.'));
        $builder->add($elementFactory->newInput('database', 'Database', 'text', 'The name of the database which is used upon connection'));
    }
}
