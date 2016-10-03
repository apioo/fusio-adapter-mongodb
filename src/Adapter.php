<?php

namespace Fusio\Adapter\Mongodb;

use Fusio\Engine\AdapterInterface;

class Adapter implements AdapterInterface
{
    public function getDefinition()
    {
        return __DIR__ . '/../definition.json';
    }
}
