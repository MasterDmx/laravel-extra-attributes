<?php

namespace MasterDmx\LaravelExtraAttributes\Entities;

use Illuminate\Support\Collection;

class AttributeCollection extends Collection
{
    /**
     * Возвращает оригинальный массив
     *
     * @return array
     */
    public function all(): array
    {
        return $this->items;
    }

    public function toArray()
    {
        return $this->export();
    }

    /**
     * Проверка наличия аттрибута в коллекции по ключу
     *
     * @param [type] $key
     * @return boolean
     */
    public function has($key): bool
    {
        return isset($this->items[$key]);
    }

    /**
     * Получить объект по ключу
     *
     * @param string|int $key Ключ
     * @param string|callback|int| $default Значение в случае, если элемен не найден
     * @return object|string|callback|int
     */
    public function get($key, $default = null)
    {
        return $this->items[$key] ?? $default;
    }

    /**
     * Клонировать коллекцию
     */
    public function clone(bool $cloneObjects = true)
    {
        if (!$cloneObjects || $this->isEmpty()) {
            return clone $this;
        }

        $items = [];

        foreach ($this as $key => $item) {
            $items[$key] = clone $item;
        }

        return new static($items);
    }

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
            $data = $attribute->export();

            if (!empty($data)) {
                $exported[$attribute->id] = $attribute->export();
            }
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
        return array_keys($this->items);
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

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Удалить пустые элементы
     *
     * @return self
     */
    public function removeEmptyElements(): self
    {
        return (clone $this)->filter(function ($el) {
            return $el->checkForEmpty();
        });
    }

    public function each(callable $callback)
    {
        foreach ($this as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * Исключить аттрибуты по ключам
     *
     * @param array $ids
     * @return self
     */
    public function intersectAttributes(array $keys)
    {
        $items = [];

        foreach ($keys as $key) {
            if ($this->has($key)) {
                $items[$key] = $this->get($key);
            }
        }

        return new static($items);
    }

}
