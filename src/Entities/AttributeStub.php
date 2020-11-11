<?php

namespace MasterDmx\LaravelExtraAttributes\Entities;

/**
 *
 * Заглушка
 *
 * @author Sergey Krening
 * @version 1.0 (2020-04-21)
 */
class AttributeStub
{
    private $returnThisMethods = ['setPattern'];

    public function __call($method, $arguments)
    {
        if (in_array($method, $this->returnThisMethods)) {
            return $this;
        }

        return null;
    }

    public function __get($property)
    {
        return null;
    }
}
