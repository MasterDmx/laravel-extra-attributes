<?php

namespace MasterDmx\LaravelExtraAttributes;

use Illuminate\Support\Collection;
use MasterDmx\LaravelExtraAttributes\Services\Comparator;

/**
 * Бандл (коллекция из коллекций)
 *
 * @version 1.0.1 2020-11-18
 */
class AttributeBundle extends Collection
{
    public function getBlock($number): AttributeCollection
    {
        return $this->hasBlock($number) ? $this->get($number) : new AttributeCollection();
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

    /**
     * Применить пресет
     *
     * @param string $preset
     * @return self
     */
    public function applyPreset(string $preset): self
    {
        $result = [];

        foreach ($this as $collection) {
            $collection = $collection->applyPreset($preset);

            if (!$collection->isEmpty()) {
                $result[] = $collection;
            }
        }

        return new static($result);
    }

    // -----------------------------------------------------------------
    // Сравнение
    // -----------------------------------------------------------------

    /**
     * Сравнить аттрибуты
     *
     * @param self|AttributeCollection $entity
     * @param boolean|null $strictly Строгое сравнение (null - пользовательский выбор)
     * @param boolean $reverse Смена мест
     * @return boolean
     */
    public function compare($entity, ?bool $strictly = null, bool $reverse = false): bool
    {
        return $reverse ? $this->getComparator()->compare($entity, $this, $strictly) : $this->getComparator()->compare($this, $entity, $strictly);
    }

    /**
     * Получить компаратор
     *
     * @return Comparator
     */
    public function getComparator(): Comparator
    {
        return new Comparator();
    }

}
