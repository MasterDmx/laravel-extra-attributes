<?php

namespace MasterDmx\LaravelExtraAttributes\Entities\Attributes;

use MasterDmx\LaravelExtraAttributes\Entities\Attribute;
use MasterDmx\LaravelHelpers\NumericHelper;

/**
 * Интервал
 * @version 1.0.1 2020-11-17
 */
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

    /**
     * Паттерны вывода
     *
     * @var array|null
     */
    private $patterns;

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


    // ----------------------------------------------------------------
    // Проверки
    // ----------------------------------------------------------------

    /**
     * Проверить наличие значений
     */
    public function hasValues()
    {
        return isset($this->min) || isset($this->max);
    }

    /**
     * Значения равны
     * @return bool
     */
    public function isEqual() : bool
    {
        return $this->min == $this->max;
    }

    /**
     * Поле полноценный интервал
     * @return bool
     */
    public function isInterval() : bool
    {
        return isset($this->min) && isset($this->max);
    }

    // --------------------------------------------------------
    // View
    // --------------------------------------------------------

    /**
     * Получение значения, указанного в редакторе
     */
    public function getValue(string $key)
    {
        return $this->$key ?? null;
    }

    /**
     * Вывод значения с форматированием
     */
    public function showValue(string $key)
    {
        return $this->formatting($this->getValue($key));
    }

    /**
     * Вывод единицы измерения
     */
    public function showUnit(string $key, bool $genitiveIncline = false)
    {
        // $unitKey = $key . 'Unit';

        // if (!isset($this->unit[$genitiveIncline ? 'inclineGenitive' : 'incline'])) {
        //     return '';
        // }

        // return StringHelper::inclineByNumericOfArray($this->getValue($key), $this->unit[$genitiveIncline ? 'inclineGenitive' : 'incline']);

        return 'Юнит';
    }

    /**
     * Показ информации в нужном виде
     *
     * @return void
     */
    public function show()
    {
        if ($key = $this->selectPattern()) {
            $result = $this->replacePatternTags($this->patterns[$key]);
        }

        $this->clearPatterns();

        return $result ?? null;
    }

    /**
     * Задать шаблон вывода
     *
     * @return self
     */
    public function setPattern($action, $pattern): self
    {
        $this->patterns[$action] = $pattern;
        return $this;
    }

    /**
     * Очистить паттерны
     *
     * @return self
     */
    public function clearPatterns(): self
    {
        $this->patterns = [];
        return $this;
    }

    /**
     * Форматрирование числа
     */
    private function formatting($value)
    {
        return (strpos($value, '.') === false && strpos($value, ',') === false) ? number_format($value, 0, ',', ' ') : round($value, 2);
    }

    private function selectPattern()
    {
        foreach (array_keys($this->patterns) ?? [] as $action) {
            if (
                $action == 'equal' && $this->isEqual() ||
                $action == 'interval' && $this->isInterval() ||
                $action == 'min' && isset($this->min) ||
                $action == 'max' && isset($this->max)
            ) {
                return $action;
            }
        }

        return null;
    }

    private function replacePatternTags(string $str)
    {
        if (!(strpos($str, '{min}') === false)) {
            $str = str_replace('{min}', $this->showValue('min'), $str);
        }

        if (!(strpos($str, '{max}') === false)) {
            $str = str_replace('{max}', $this->showValue('max'), $str);
        }

        if (!(strpos($str, '{value}') === false)) {
            $str = str_replace('{value}', $this->showValue('min'), $str);
        }

        if (!(strpos($str, '{minUnit}') === false)) {
            $str = str_replace('{minUnit}', $this->showUnit('min'), $str);
        }

        if (!(strpos($str, '{minUnitGenitive}') === false)) {
            $str = str_replace('{minUnitGenitive}', $this->showUnit('min', true), $str);
        }

        if (!(strpos($str, '{maxUnit}') === false)) {
            $str = str_replace('{maxUnit}', $this->showUnit('max'), $str);
        }

        if (!(strpos($str, '{maxUnitGenitive}') === false)) {
            $str = str_replace('{maxUnitGenitive}', $this->showUnit('max', true), $str);
        }

        return $str;
    }

    // --------------------------------------------------------
    // Base
    // --------------------------------------------------------

    /**
     * Сравнение с другим полем
     *
     * @return bool
     */
    public function compare($attribute): bool
    {
        return NumericHelper::compareIntervals($this->min, $this->max, $attribute->min, $attribute->max);
    }

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
