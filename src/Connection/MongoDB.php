<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2022 Christoph Kappestein <christoph.kappestein@gmail.com>
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

use Fusio\Engine\Connection\PingableInterface;
use Fusio\Engine\ConnectionAbstract;
use Fusio\Engine\Exception\ConfigurationException;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use MongoDB\Client;
use MongoDB\Database;
use MongoDB\Exception\Exception;
use MongoDB\Model\BSONDocument;

/**
 * MongoDB
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org/
 */
class MongoDB extends ConnectionAbstract implements PingableInterface
{
    public function getName(): string
    {
        return 'MongoDB';
    }

    public function getConnection(ParametersInterface $config): Database
    {
        $database   = $config->get('database');
        $options    = [];
        $rawOptions = $config->get('options');
        if (!empty($rawOptions)) {
            parse_str($rawOptions, $options);
        }

        if (class_exists('MongoDB\Client')) {
            $client = new Client($config->get('url'), $options);

            return $client->selectDatabase($database);
        } else {
            throw new ConfigurationException('PHP extension "mongod" is not installed');
        }
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newInput('url', 'Url', 'text', 'The connection string for the database i.e. <code>mongodb://localhost:27017</code>'));
        $builder->add($elementFactory->newInput('options', 'Options', 'text', 'Optional options for the connection'));
        $builder->add($elementFactory->newInput('database', 'Database', 'text', 'The name of the database which is used upon connection'));
    }

    public function ping(mixed $connection): bool
    {
        if ($connection instanceof Database) {
            try {
                $cursor   = $connection->command(['ping' => 1]);
                $response = $cursor->toArray()[0] ?? null;

                return $response instanceof BSONDocument && isset($response['ok']) && $response['ok'] == 1;
            } catch(Exception $e) {
            }
        }

        return false;
    }
}
