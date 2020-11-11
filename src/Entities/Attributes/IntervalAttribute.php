<?php

namespace MasterDmx\LaravelExtraAttributes\Entities\Attributes;

use MasterDmx\LaravelExtraAttributes\Entities\Attribute;

class IntervalAttribute extends Attribute
{
    /**
     * Минимальное значение
     *
     * @var int|float
     */
    public $min;

    /**
     * Максимальное значение
     *
     * @var int|float
     */
    public $max;

    // --------------------------------------------------------
    // Helpers
    // --------------------------------------------------------

    protected function clearValue($value)
    {
        $result = trim(preg_replace('/[^0-9.,]/', '', $value));

        if (!(strpos($value, ',') === false)) {
            $result = str_ireplace(",", ".", $result);
        }

        return $result;
    }

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
        $this->min = $data['min'] ?? null;
        $this->max = $data['max'] ?? null;

        if (isset($data['minRaw'])) {
            $this->min = $this->clearValue($data['minRaw']);
        }

        if (isset($data['maxRaw'])) {
            $this->max = $this->clearValue($data['maxRaw']);
        }
    }

    /**
     * Экспорт значений
     *
     * @return array|int|string|double|float
     */
    public function export()
    {
        return array_filter([
            'min' => $this->min,
            'max' => $this->max,
        ], function ($el) {
            return isset($el);
        });
    }
}
