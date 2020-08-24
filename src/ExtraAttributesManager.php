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
    public function get(string $alias, array $import = null, bool $intersect = true, bool $skipEmpty = true): AttributeCollection
    {
        return $this->getContext($alias)->createCollection($import, $intersect, $skipEmpty);
    }

    /**
     * Получить аттрибуты контеска
     *
     * @param string $alias Алиас контекста
     * @param array $import Данные для заполнения
     * @param bool $intersect Оставить только заполненные
     * @return \MasterDmx\LaravelExtraAttributes\Entities\AttributeCollection
     */
    public function collection(string $alias, array $import = null, bool $intersect = true, bool $skipEmpty = true): AttributeCollection
    {
        return $this->getContext($alias)->createCollection($import, $intersect, $skipEmpty);
    }

    public function bundle(string $alias, array $import = null, bool $intersect = true, bool $skipEmpty = true)
    {
        # code...
    }

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

    public function clearInputData(array $data)
    {
        $new = [];

        foreach ($data as $key => $elem) {
            if (is_array($elem)) {
                $elem = $this->clearInputData($elem);
            }

            if (empty($elem) && $elem != '0') {
                continue;
            }

            $new[$key] = $elem;
        }

        return $new;
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
}
