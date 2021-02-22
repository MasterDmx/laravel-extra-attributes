<?php

namespace MasterDmx\LaravelExtraAttributes;

class AttributeViewCollection
{
    /**
     * Конфиг
     */
    private array $config;

    /**
     * Коллекция всех аттрибутов контекста
     */
    private AttributeCollection|null $collection;

    /**
     * Коллекция заполненных аттрибутов
     */
    private AttributeCollection|null $filled;

    /**
     * Параметры
     */
    private array $options = [];

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

    /**
     * Режим работы бандла
     *
     * @var boolean
     */
    private $bundleMode = false;

    /**
     * Номер блока бандла
     *
     * @var boolean
     */
    private $bundleBlock;

    public function __construct(array $config, AttributeCollection $collection = null, AttributeCollection $filled = null, array $extra = null)
    {
        $this->config = $config;
        $this->collection = $collection;
        $this->filled = $filled;
        $this->extra = $extra;

        if (!isset($this->filled)) {
            $this->filled = new AttributeCollection();
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
        if (isset($this->config['template'])) {
            return (string)view($this->config['templates']['ui'], [
                'render' => $this,
                'extra' => $this->extra,
            ]);
        }


        if ($this->config['mode'] === 'by_groups') {
            return $this->showListByGroups();
        }

        return $this->showList();
    }

    /**
     * Отрисовать аттрибуты
     *
     * @return string
     */
    public function showAttributes(): string
    {
        return $this->showList();
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
    public function showListByGroups(bool $filled = false): string
    {
        $content = [];

        foreach ($this->getListByGroups($filled) as $groupId => $group) {
            $list = [];

            foreach ($group['attributes'] as $attribute) {
                $list[] = $this->showAttribute($attribute);
            }

            if (!empty($this->config['template_group'])) {
                $content[] = (string)view($this->config['template_group'], [
                    'name' => $group['name'] ?? 'Без имени',
                    'content' => implode('', $list)
                ]);

                continue;
            }

            foreach ($list as $item) {
                $content[] = $item;
            }
        }

        return $content ? implode('', $content) : '';
    }

    /**
     * Отрисовать аттрибут
     *
     * @param Attribute $attribute
     * @return string
     */
    public function showAttribute(Attribute $attribute): string
    {
        $alias = $aliasName = $this->config['alias'];

        if ($this->bundleMode) {
            $alias .= '[' . $this->bundleBlock . ']';
        }

        $params = [
            'attribute' => $attribute,
            'alias'     => $alias,
            'aliasName' => $aliasName,
            'block'     => $this->bundleBlock,
            'extra'     => $this->extra,
        ];

        $content = (string)view($this->config['template_items_prefix'] . $attribute->viewTemplate, $params);

        if (isset($this->config['template_item'])) {
            $content = (string)view($this->config['template_item'], $params + ['content' => $content]);
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
     */
    public function getAttribute($id, bool $filled = false): Attribute
    {
        return $this->getCollection($filled)->get($id);
    }

    // ------------------------------------------------------------------
    // Other
    // ------------------------------------------------------------------

    /**
     * Получить коллекцию
     *
     * @param boolean $filled
     * @return AttributeCollection
     */
    public function getCollection(bool $filled = false): AttributeCollection
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

    /**
     * Включить режим бандла
     *
     * @param integer $block
     * @return static
     */
    public function enableBundleMode(int $block)
    {
        $this->bundleMode = true;
        $this->bundleBlock = $block;

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
