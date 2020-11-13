<?php

namespace MasterDmx\LaravelExtraAttributes;

use Illuminate\Container\EntryNotFoundException;
use Illuminate\Contracts\Foundation\Application;
use MasterDmx\LaravelExtraAttributes\Entities\Collection;
use MasterDmx\LaravelExtraAttributes\Entities\Bundle;
use MasterDmx\LaravelExtraAttributes\Entities\Context;
use MasterDmx\LaravelExtraAttributes\View\Initializer;

class ExtraAttributesManager
{
    /**
     * Контексты
     *
     * @var array
     */
    private $contexts = [];

    /**
     * IoC
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    private $app;

    public function __construct(Application $app, array $contexts = [])
    {
        $this->app = $app;
        $this->defineContexts($contexts);
    }

    /**
     * Получить название класса контекста по алиасу
     *
     * @param string $alias
     * @return string|null
     */
    public function getConxtextClass(string $alias): string
    {
        if (!isset($this->contexts[$alias])) {
            throw new EntryNotFoundException ('Context alias ' . $alias . ' not defined');
        }

        return $this->contexts[$alias];
    }

    /**
     * Получить инстанс контекста
     *
     * @param string $alias
     * @return \MasterDmx\LaravelExtraAttributes\Entities\Context
     */
    public function getContext(?string $class): Context
    {
        try {
            return $this->app->get($class);
        } catch (EntryNotFoundException $th) {
            throw new EntryNotFoundException ('Context ' . $class . ' not defined');
        }
    }

    /**
     * Получить инстанс контекста по его алиасу
     *
     * @param string $alias
     * @return \MasterDmx\LaravelExtraAttributes\Entities\Context
     */
    public function getContextByAlias(string $alias): Context
    {
        return $this->getContext($this->getConxtextClass($alias));
    }

    public function addAttribute(string $alias, string $key, $value)
    {
        return $this->collection($alias, [$key => $value], true);
    }

    /**
     * Получить алиас контекста по названию класса
     *
     * @param string $class
     * @return mixed
     */
    public function getContextAliasByClass(string $class, $default = null)
    {
        return array_flip($this->contexts)[$class] ?? $default;
    }

    /**
     * Получить аттрибуты контеска
     *
     * @param string $alias Алиас контекста
     * @param array $import Данные для заполнения
     * @param bool $intersect Оставить только заполненные
     * @return \MasterDmx\LaravelExtraAttributes\Entities\Collection
     */
    public function collection(string $alias, array $import = null, bool $intersect = true, bool $skipEmpty = true): Collection
    {
        return $this->getContextByAlias($alias)->createCollection($import, $intersect, $skipEmpty);
    }

    /**
     * Получить аттрибуты бандла
     *
     * @param string $alias Алиас контекста
     * @param array $import Данные для заполнения
     * @param bool $intersect Оставить только заполненные
     * @return \MasterDmx\LaravelExtraAttributes\Entities\Bundle
     */
    public function bundle(string $alias, array $import = null, bool $intersect = true, bool $skipEmpty = true): Bundle
    {
        return $this->getContextByAlias($alias)->createBundle($import, $intersect, $skipEmpty);
    }

    /**
     * Сформировать пользовательский интерфейс
     *
     * @param string $alias Псевдоним контекста
     * @param mixed $fill Коллекция атрибутов для заполнения
     * @return \MasterDmx\LaravelExtraAttributes\View\Initializer
     */
    public function view(string $alias): Initializer
    {
        return new Initializer($this->getContextByAlias($alias));
    }

    /**
     * Экспорт значений атрибутов в массив
     *
     * @return void
     */
    public function export(Collection $collection)
    {
        return $collection->export();
    }

    // -------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------

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

    // -------------------------------------------------------------
    // INIT
    // -------------------------------------------------------------

    /**
     * Установить контекст
     *
     * @param string $alias
     * @param string $class
     * @return self
     */
    public function defineContext(string $alias, string $class): self
    {
        $this->contexts[$alias] = $class;
        $this->app->singleton($class);

        return $this;
    }

    /**
     * Установить несколько контекстов
     *
     * @param array $list
     * @return void
     */
    public function defineContexts(array $list = []): void
    {
        foreach ($list as $alias => $class) {
            $this->defineContext($alias, $class);
        }
    }
}
