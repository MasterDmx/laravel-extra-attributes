<?php

namespace MasterDmx\LaravelExtraAttributes\Entities;

use App\Models\Product\Product;
use MasterDmx\ObjectCollection\ObjectCollection;

/**
 * Коллекция атрибутов
 */
class AttributeCollection extends ObjectCollection
{
    /**
     * Пресет
     *
     * @var [type]
     */
    private $preset;

    /**
     * Импорт
     *
     * @param mixed $data Данные
     * @return self
     */
    public function import($data): self
    {
        $this->each(function ($attribute) use ($data) {
            if (isset($data[$attribute->id])) {
                $attribute->import($data[$attribute->id]);
            }
        });

        return $this;
    }

    /**
     * Преобразовать коллекцию атрибутов в массив значений
     *
     * @return array
     */
    public function export(): array
    {
        $exported = [];

        $this->each(function ($attribute) use (&$exported) {
            $exported[$attribute->id] = $attribute->export();
        });

        return $exported;
    }

    /**
     * Заменить атрибуты из другой коллекции
     *
     * @param self $collection
     * @param boolean $intersect Исключить
     * @return void
     */
    public function replaceAttributesFrom(self $collection, bool $intersect = false)
    {
        $items = [];

        foreach ($this as $key => $item) {
            if ($collection->has($key)) {
                $items[$key] = $collection->get($key);
                continue;
            }

            if ($intersect) {
                continue;
            }

            $items[$key] = $item;
        }

        return new static($items);
    }

    public function getIds()
    {
        return $this->getKeys();
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

        foreach ($this as $id => $item) {
            if (!$item->hasPreset($preset)) {
                continue;
            }

            $result[$id] = $item->applyPreset($preset);
        }

        return new static($result);
    }

    /**
     * Исключить аттрибуты по ключам
     *
     * @param array $ids
     * @return self
     */
    public function intersect(array $ids): self
    {
        return parent::intersect($ids);
    }
}
