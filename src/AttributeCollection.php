<?php

namespace MasterDmx\LaravelExtraAttributes;

use Illuminate\Support\Collection;
use MasterDmx\LaravelExtraAttributes\Contracts\Validateable;
use MasterDmx\LaravelExtraAttributes\Services\Comparator;

/**
 * Коллекция аттрибутов
 * @version 1.0.0 2020-11-17
 */
class AttributeCollection extends Collection
{
    /**
     * Алиас текущего контекста
     *
     * @var string|null
     */
    // protected $contextAlias;

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
        return $this->items[$key] ?? (isset($default) ? $default : $this->newStub());
    }

    private function newStub()
    {
        $class = config('attrubutes.stub', \MasterDmx\LaravelExtraAttributes\Entities\Stub::class);
        return new $class();
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
     * @param array $data Данные
     * @param array $validate Провалидировать сырые данные
     * @param array $intersect Удалить пустые
     * @return self
     */
    public function import(array $data, bool $validate = false, bool $intersect = true): self
    {
        foreach ($this as $key => $attribute) {
            if (isset($data[$attribute->id])) {
                if ($validate && is_a($attribute, Validateable::class)) {
                    if (!$attribute->isValidRaw($data[$attribute->id])) {
                        $this->forget($key);
                        continue;
                    }

                    $attribute->importRaw($data[$attribute->id]);

                    if (!$attribute->isValid()) {
                        $this->forget($key);
                        continue;
                    }
                } else {
                    $attribute->import($data[$attribute->id]);
                }
            } elseif ($intersect) {
                $this->forget($key);
            }
        }

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

    /**
     * Очистить
     *
     * @return self
     */
    public function clear(): self
    {
        $this->items = [];
        return $this;
    }

    // -----------------------------------------------------------------
    // Сравнение
    // -----------------------------------------------------------------

    /**
     * Сравнить аттрибуты
     *
     * @param self|AttributeBundle $entity
     * @param boolean|null $strictly Строгое сравнение (null - пользовательский выбор)
     * @param boolean|null $reverse Смена мест
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

    // ------------------------------------------------------------------
    // База
    // ------------------------------------------------------------------

    /**
     * Установить алиас контекста
     *
     * @param string $alias
     * @return static
     */
    // public function defineContextAlias(string $alias)
    // {
    //     $this->contextAlias = $alias;

    //     return $this;
    // }
}
