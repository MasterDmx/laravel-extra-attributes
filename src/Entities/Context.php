<?php

namespace MasterDmx\LaravelExtraAttributes\Entities;

use ErrorException;
use InvalidArgumentException;
use MasterDmx\LaravelExtraAttributes\Entities\AttributeCollection;
use MasterDmx\LaravelExtraAttributes\Entities\Type;
use MasterDmx\LaravelExtraAttributes\ExtraAttributesManager;

abstract class Context
{
    /**
     * Атрибуты
     *
     * @var \MasterDmx\LaravelExtraAttributes\Entities\AttributeCollection
     */
    private $attributes;

    /**
     * Пресеты
     *
     * @var array
     */
    private $presets = [];

    public function __construct()
    {
        $attributes = $this->attributes();

        if (empty($attributes)) {
            throw new InvalidArgumentException('Attributes is missing');
        }

        $instances = [];

        foreach ($attributes as $id => $attribute) {
            if (empty($attribute['entity']) || !class_exists($attribute['entity'])) {
                throw new InvalidArgumentException('Undefined entity ' . $attribute['type'] ?? 'NULL' . ' in ' . $attribute['id'] . ' attribute');
            }

            $instance = new $attribute['entity']($attribute + ['id' => $id]);
            $instances[$instance->id] = $instance;
        }

        $this->attributes = new AttributeCollection($instances);
    }

    /**
     * Создать коллекцию
     *
     * @param array|null $import Массив для насыщения значений
     * @param bool $intersect Оставить только заполненные
     * @return AttributeCollection
     */
    public function createCollection(array $import = null, bool $intersect = true, bool $skipEmptiness = true): AttributeCollection
    {
        $collection = $this->getAttributes()->clone();

        if (!empty($import)) {
            if ($intersect) {
                $collection = $collection->intersect(array_keys($import));
            }

            $collection->import($import);

            if ($skipEmptiness) {
                $collection = $collection->removeEmptyElements();
            }
        }

        return $collection;
    }

    // -----------------------------------------------------------
    // Attributes
    // -----------------------------------------------------------

    /**
     * Получить атрибуты
     *
     * @return \MasterDmx\LaravelExtraAttributes\Entities\AttributeCollection
     */
    public function getAttributes(): AttributeCollection
    {
        return $this->attributes;
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
