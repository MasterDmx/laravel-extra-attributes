<?php

namespace MasterDmx\LaravelExtraAttributes;

use ErrorException;
use InvalidArgumentException;
use MasterDmx\LaravelExtraAttributes\Entities\Collection;

abstract class AttributeContext
{
    /**
     * Название поля для хранения в БД. Используется для GUI редактирования
     *
     * @var string
     */
    public string $alias = 'extra_attributes';

    /**
     * Контекст работает с бандлом
     *
     * @var bool
     */
    public bool $isBundle = false;

    /**
     * Атрибуты
     *
     * @var AttributeCollection
     */
    private $attributes;

    public function __construct()
    {
        $attributes = $this->attributes();

        if (empty($this->alias)) {
            throw new ErrorException('Context alias cannot be empty');
        }

        if (empty($attributes)) {
            throw new InvalidArgumentException('Attributes is missing');
        }

        $instances = [];

        foreach ($attributes as $id => $attribute) {
            $instance = new $attribute['handler']($attribute + ['id' => $id]);
            $instances[$instance->id] = $instance;
        }

        $this->attributes = new AttributeCollection($instances);
    }

    /**
     * Создать коллекцию
     */
    public function newCollection(array $import = null, bool $validate = false): AttributeCollection
    {
        $collection = $this->getAttributes()->clone();

        return !empty($import) ? $collection->import($import, $validate, true) : $collection->clear();
    }

    /**
     * Создать бандл
     */
    public function newBundle(array $import = null, bool $validate = false): AttributeBundle
    {
        $bundle = new AttributeBundle();

        foreach ($import ?? [] as $attributes) {
            $bundle->add($this->newCollection($attributes, $validate));
        }

        return $bundle;
    }

    // -----------------------------------------------------------
    // Attributes
    // -----------------------------------------------------------

    /**
     * Получить атрибуты
     *
     * @return AttributeCollection
     */
    public function getAttributes(bool $clone = true): AttributeCollection
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
    protected function entities(): ?array
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
