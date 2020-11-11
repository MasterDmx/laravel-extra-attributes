<?php

namespace MasterDmx\LaravelExtraAttributes\View;

use MasterDmx\LaravelExtraAttributes\Entities\Attribute;
use MasterDmx\LaravelExtraAttributes\Entities\Collection;

class Render
{
    /**
     * Конфиг
     *
     * @var array
     */
    private $config;

    /**
     * Коллекция всех аттрибутов контекста
     *
     * @var \MasterDmx\LaravelExtraAttributes\Entities\Collection|null
     */
    private $collection;

    /**
     * Коллекция заполненных аттрибутов
     *
     * @var \MasterDmx\LaravelExtraAttributes\Entities\Collection|null
     */
    private $filled;

    /**
     * Инициализатор ДО процесса инициализации
     *
     * @var \MasterDmx\LaravelExtraAttributes\View\initializer|null
     */
    private $initializer;

    /**
     * Параметры
     *
     * @var array
     */
    private $options = [];

    /**
     * Доп. данные, передающиеся в шаблоны
     *
     * @var array
     */
    private $extra = [];

    /**
     * Статус насыщения основной коллекции контекста
     *
     * @var bool
     */
    private $saturated = false;

    public function __construct(array $config, Collection $collection = null, Collection $filled = null, array $extra = null, Initializer $initializer = null)
    {



        $this->config = $config;
        $this->collection = $collection;
        $this->filled = $filled;
        $this->extra = $extra;
        $this->initializer = $initializer;

        if (!isset($this->filled)) {
            $this->filled = new Collection();
        }
    }

    public function __toString()
    {
        return $this->show();
    }

    // ------------------------------------------------------------------
    // Show
    // ------------------------------------------------------------------

    /**
     * Отобразить UI
     *
     * @return string
     */
    public function show(): string
    {
        return view($this->config['templates']['ui'], [
            'render' => $this,
            'extra' => $this->extra,
        ]);
    }

    /**
     * Показ списка
     *
     * @return string
     */
    public function showList(bool $filled = false): string
    {
        $content = '';

        foreach ($this->getList($filled) as $attribute) {
            $content .= $this->showAttribute($attribute);
        }

        return $content;
    }

    /**
     * Показ списка по группам
     *
     * @return string
     */
    // public function showListByGroups(bool $filled = false): string
    // {
    //     $content = [];

    //     foreach ($this->getListByGroups($filled) as $groupId => $group) {
    //         if (empty($group['ids'])) {
    //             continue;
    //         }

    //         $list = [];

    //         foreach ($group['ids'] as $id) {
    //             if ($onlyFilled && !$this->filled->has($id)) {
    //                 continue;
    //             }

    //             $list[] = $this->showAttribute($id, $onlyFilled);
    //         }

    //         if (empty($list)) {
    //             continue;
    //         }

    //         if ($this->config['templates']['group'] ?? false) {
    //             $content[] = view($this->config['templates']['group'], [
    //                 'name' => $group['name'] ?? 'Без имени',
    //                 'content' => implode('', $list)
    //             ]);

    //             continue;
    //         }

    //         foreach ($list as $item) {
    //             $content[] = $item;
    //         }
    //     }

    //     return $content ? implode('', $content) : '';
    // }

    /**
     * Отрисовать аттрибут
     *
     * @param string|int|float $id
     * @return string
     */
    public function showAttribute(Attribute $attribute): string
    {



        $template = $this->config['templates']['entities'][$attribute->entity];
        $content = view($template, [
            'attribute' => $attribute,
            'class' => get_class($attribute),
            'name' => $this->config['name'] ?? 'undefined',
            'extra' => $this->extra,
        ]);

        if (isset($this->config['templates']['attribute'])) {
            return view($this->config['templates']['attribute'], [
                'content' => $content,
                'attribute' => $attribute,
                'extra' => $this->extra,
            ]);
        }

        return $content;
    }

    /**
     * Отрисовать аттрибут по ID
     *
     * @param string|int|float $id
     * @return string
     */
    public function showAttributeById($id, bool $filled = false): string
    {
        return $this->showAttribute($this->getAttribute($id, $filled));
    }

    // ------------------------------------------------------------------
    // Get
    // ------------------------------------------------------------------

    /**
     * Получить список аттрибутов по группам
     *
     * @param boolean $filled
     * @return array
     */
    public function getListByGroups(bool $filled = false): array
    {
        foreach ($this->config['groups'] as $groupId => $group) {
            if (empty($group['ids'])) {
                continue;
            }

            $list = [];

            foreach ($group['ids'] as $id) {
                if ($filled && !$this->filled->has($id)) {
                    continue;
                }

                $list[] = $this->getAttribute($id, $filled);
            }

            if (!empty($list)) {
                $result[$groupId] = [
                    'name' => $group['name'],
                    'attributes' => $list
                ];
            }
        }

        return $result ?? [];
    }

    /**
     * Показ списка
     *
     * @return string
     */
    public function getList(bool $filled = false): array
    {
        if (isset($this->options['sort']) && $this->options['sort'] == 'position') {
            foreach ($this->getCollection($filled) as $attribute) {
                $list[] = $this->getAttribute($attribute->id, $filled);
            }
        } else {
            foreach ($this->config['attributes'] as $id) {
                if ($filled && !$this->filled->has($id)) {
                    continue;
                }

                $list[] = $this->getAttribute($id, $filled);
            }
        }

        $this->clearOptions();

        return $list ?? [];
    }

    /**
     * Получить аттрибут коллекции
     *
     * @param string|int $id
     * @param boolean $filled
     * @return Attribute
     */
    public function getAttribute($id, bool $filled = false): Attribute
    {
        return $this->getCollection($filled)->get($id);
    }



    // ------------------------------------------------------------------
    // Other
    // ------------------------------------------------------------------

    /**
     * Инициализировать еще один один UI
     *
     * @return void
     */
    public function view()
    {
        # code...
    }

    /**
     * Получить коллекцию
     *
     * @param boolean $filled
     * @return Collection
     */
    public function getCollection(bool $filled = false): Collection
    {
        if ($filled) {
            return $this->filled;
        }

        if (!$this->saturated) {
            if (!$this->filled->isEmpty()) {
                $this->collection = $this->collection->replaceAttributesFrom($this->filled);
            }

            $this->saturated = true;
        }

        return $this->collection;
    }

    /**
     * Очистить параметры
     *
     * @return self
     */
    public function clearOptions(): self
    {
        $this->options = [];
        return $this;
    }

    // --------------------------------------------------------------
    // Options
    // --------------------------------------------------------------

    /**
     * Сортировать список
     *
     * @param string $param (config - по конфигу, position - по позиции в коллекции)
     * @return self
     */
    public function sort(string $param = 'config'): self
    {
        $this->options['sort'] = $param;

        return $this;
    }

    /**
     * Установить доп. данные
     *
     * @param string|int|array $alias
     * @param mixed $value
     * @return self
     */
    public function extra($alias = null, $value = null): self
    {
        if (!empty($alias)) {
            if (is_array($alias)) {
                foreach ($alias as $key => $value) {
                    $this->extra($key, $value);
                }
            } elseif (!isset($value) && isset($this->extra[$alias])) {
                unset($this->extra[$alias]);
            } else {
                $this->extra[$alias] = $value;
            }
        }

        return $this;
    }
}
