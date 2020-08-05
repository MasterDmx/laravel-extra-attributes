<?php

namespace MasterDmx\LaravelExtraAttributes;

use Psr\Container\ContainerInterface;
use Illuminate\Container\EntryNotFoundException;
use MasterDmx\LaravelExtraAttributes\Entities\AttributeCollection;
use MasterDmx\LaravelExtraAttributes\Entities\Context;

class ExtraAttributesManager
{
    /**
     * Преффикс для сохранения классов в контейнер
     */
    const CONTAINER_ALIAS_PREFFIX = 'EA_';

    /**
     * IoC
     *
     * @var \Psr\Container\ContainerInterface
     */
    private $container;

    public static function getContextAliasForContainer(string $alias): string
    {
        return static::CONTAINER_ALIAS_PREFFIX . $alias;
    }

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Получить инстанс контекста
     *
     * @param string $alias
     * @return \MasterDmx\LaravelExtraAttributes\Entities\Context
     */
    public function getContext(string $alias): Context
    {
        try {
            return $this->container->get(static::getContextAliasForContainer($alias));
        } catch (EntryNotFoundException $th) {
            throw new EntryNotFoundException ('Context ' . $alias . ' not defined');
        }
    }

    /**
     * Получить аттрибуты контеска
     *
     * @param string $alias Алиас контекста
     * @param array $import Данные для заполнения
     * @param bool $intersect Оставить только заполненные
     * @return \MasterDmx\LaravelExtraAttributes\Entities\AttributeCollection
     */
    public function get(string $alias, array $import = null, bool $intersect = true): AttributeCollection
    {
        return $this->getContext($alias)->createCollection($import, $intersect);
    }

    /**
     * Импорт значений атрибутов из массива
     *
     * @return \MasterDmx\LaravelExtraAttributes\Entities\AttributeCollection
     */
    // public function import(array $data, string $alias): AttributeCollection
    // {
    //     return $this->get($alias)->intersect(array_keys($data))->import($data);
    // }

    /**
     * Сформировать пользовательский интерфейс
     *
     * @param string $alias Псевдоним контекста
     * @param mixed $fill Коллекция атрибутов для заполнения
     * @return \MasterDmx\LaravelExtraAttributes\View\Handler
     */
    public function view(string $alias)
    {
        return new View\Handler($this->getContext($alias));
    }












    /**
     * Экспорт значений атрибутов в массив
     *
     * @return void
     */
    public function export(AttributeCollection $collection)
    {
        return $collection->export();
    }

    /**
     * Смапить аттрибуты
     *
     * @param array $data
     * @param string $context
     * @return array
     */
    public function map(array $data, string $contextAlias)
    {
        return $this->contextManager->find($contextAlias)->getAttributes()->clone()->intersect(array_keys($data))->import($data);
    }



}