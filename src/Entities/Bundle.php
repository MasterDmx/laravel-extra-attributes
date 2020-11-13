<?php

namespace MasterDmx\LaravelExtraAttributes\Entities;

use Illuminate\Support\Collection as LaravelCollection;

class Bundle extends LaravelCollection
{
    public function getBlock($number): Collection
    {
        return $this->hasBlock($number) ? $this->get($number) : new Collection();
    }

    /**
     * Проверить наличие блока
     *
     * @param int $number
     * @return boolean
     */
    public function hasBlock(int $number): bool
    {
        return isset($this->items[$number]);
    }

    /**
     * Преобразовать коллекцию атрибутов в массив значений
     *
     * @return array
     */
    public function export(): array
    {
        $exported = [];

        $this->each(function ($collection) use (&$exported) {
            $exported[] = $collection->export();
        });

        return $exported;
    }
}
