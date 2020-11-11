<?php

namespace MasterDmx\LaravelExtraAttributes\Entities\Attributes;

use MasterDmx\LaravelExtraAttributes\Entities\Attribute;

class StringAttribute extends Attribute
{
    public $value;

    // --------------------------------------------------------
    // Base
    // --------------------------------------------------------

    /**
     * Импорт значений
     *
     * @param array|int|string|double|float $data
     * @return void
     */
    public function import($data): void
    {
        $this->value = $data;
    }

    /**
     * Экспорт значений
     *
     * @return array|int|string|double|float
     */
    public function export()
    {
        return $this->value;
    }

    /**
     * Проверка на пустоту хранящихся значений
     *
     * @return bool
     */
    public function checkForEmpty(): bool
    {
        return !empty($this->value);
    }
}
