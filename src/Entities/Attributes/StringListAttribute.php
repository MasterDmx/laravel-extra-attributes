<?php

namespace MasterDmx\LaravelExtraAttributes\Entities\Attributes;

use MasterDmx\LaravelExtraAttributes\Entities\Attribute;

class StringListAttribute extends Attribute
{
    /**
     * Значения
     *
     * @var array|null
     */
    public $values = [];

    // --------------------------------------------------------
    // Base
    // --------------------------------------------------------

    /**
     * Импорт значений из массива
     *
     * @param array|int|string|double|float $data
     * @return void
     */
    public function import($data): void
    {
        $this->values = $data ?? [];
    }

    /**
     * Экспорт значений
     *
     * @return array|int|string|double|float
     */
    public function export()
    {
        return $this->values;
    }
}
