<?php

namespace MasterDmx\LaravelExtraAttributes\Entities;

use InvalidArgumentException;
use MasterDmx\LaravelExtraAttributes\Entities\Collection;
use MasterDmx\LaravelExtraAttributes\ExtraAttributesManager;

abstract class Context
{
    /**
     * Алиас контекста
     *
     * @var [type]
     */
    public $alias;

    /**
     * Классы сущностей контекста
     *
     * @var array|null
     */
    private $entities;

    /**
     * Атрибуты
     *
     * @var \MasterDmx\LaravelExtraAttributes\Entities\Collection
     */
    private $attributes;

    public function __construct()
    {
        $this->alias = app(ExtraAttributesManager::class)->getContextAliasByClass(get_called_class());
        $this->entities = config('attrubutes.entities');

        foreach ($this->entities() as $alias => $class) {
            if ($class === null && isset($this->entities[$alias])) {
                unset($this->entities[$alias]);
            }

            $this->entities[$alias] = $class;
        }

        $attributes = $this->attributes();

        if (empty($attributes)) {
            throw new InvalidArgumentException('Attributes is missing');
        }

        $instances = [];

        foreach ($attributes as $id => $attribute) {
            $class = $this->getEntityClass($attribute['entity']);
            $instance = new $class($attribute + ['id' => $id]);
            $instances[$instance->id] = $instance;
        }

        $this->attributes = new Collection($instances);
    }

    /**
     * Создать коллекцию
     *
     * @param array|null $import Массив для насыщения значений
     * @param bool $intersect Оставить только заполненные
     * @return Collection
     */
    public function createCollection(array $import = null, bool $intersect = true, bool $skipEmptiness = true): Collection
    {
        $collection = $this->getAttributes()->clone();

        if (!empty($import)) {
            if ($intersect) {
                $collection = $collection->intersectAttributes(array_keys($import));
            }

            $collection->import($import);

            if ($skipEmptiness) {
                $collection = $collection->removeEmptyElements();
            }
        } elseif ($intersect) {
            $collection->clear();
        }

        return $collection;
    }

    /**
     * Создать банд
     *
     * @param array|null $import Массив для насыщения значений
     * @param bool $intersect Оставить только заполненные
     * @return Bundle
     */
    public function createBundle(array $import = null, bool $intersect = true, bool $skipEmptiness = true): Bundle
    {
        $bundle = new Bundle();

        foreach ($import ?? [] as $group => $attributes) {
            $bundle->add($this->createCollection($attributes, $intersect, $skipEmptiness));
        }

        return $bundle;
    }


    // -----------------------------------------------------------
    // Attributes
    // -----------------------------------------------------------

    /**
     * Получить атрибуты
     *
     * @return \MasterDmx\LaravelExtraAttributes\Entities\Collection
     */
    public function getAttributes(bool $clone = true): Collection
    {
        return $clone ? $this->attributes->clone() : $this->attributes;
    }

    public function getEntityClass(string $alias, bool $checkExist = true): string
    {
        if (!isset($this->entities[$alias])) {
            throw new InvalidArgumentException('Entity ' . ($alias ?? 'NULL') . ' unregistered');
        }

        if (!class_exists($this->entities[$alias])) {
            throw new InvalidArgumentException('Entity ' . ($alias ?? 'NULL') . ' class not found');
        }

        return $this->entities[$alias];
    }

    // -----------------------------------------------------------
    // Initial data
    // -----------------------------------------------------------

    /**
     * Импорт атрибутов из массива
     *
     * @return array
     */
    abstract protected function attributes();

    /**
     * Регистрация сущностей для контекста
     *
     * @return array
     */
    protected function entities(): array
    {
        return [];
    }

    /**
     * Загрузка типов
     *
     * @return array
     */
    protected function presets()
    {
        return null;
    }

    /**
     * Загрузка типов
     *
     * @return array
     */
    public function views()
    {
        return null;
    }
}
