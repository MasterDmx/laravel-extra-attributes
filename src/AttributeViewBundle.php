<?php

namespace MasterDmx\LaravelExtraAttributes;

use MasterDmx\LaravelExtraAttributes\Entities\Bundle;
use MasterDmx\LaravelExtraAttributes\Entities\Collection;

class AttributeViewBundle
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
     * Бандл заполненнех аттрибутов
     */
    private AttributeBundle|null $filled;

    /**
     * Доп. данные, передающиеся в шаблоны
     */
    private array|null $extra = [];

    /**
     * Инициализированные рендеры
     *
     * @var array
     */
    private $renders;

    public function __construct(array $config, AttributeCollection $collection = null, AttributeBundle $filled = null, array $extra = null)
    {
        $this->config = $config;
        $this->collection = $collection;
        $this->filled = $filled;
        $this->extra = $extra;

        if (!isset($this->filled)) {
            $this->filled = new AttributeBundle();
        }
    }

    public function __toString()
    {
        return $this->ui();
    }

    /**
     * Отобразить UI
     *
     * @return string
     */
    public function ui(): string
    {
        return (string)view($this->config['template'], [
            'view'         => $this,
            'ui'           => $this->config['ui'] ?? '',
            'preset'       => $this->config['preset'] ?? '',
            'contextClass' => $this->config['context_class'] ?? '',
            'extra'        => $this->extra,
        ]);
    }

    public function block(int $number)
    {
        if (isset($this->renders[$number])) {
            return $this->renders[$number];
        }

        return $this->renders[$number] = $this->render($this->filled->getBlock($number), $number);
    }

    /**
     * Рендер
     *
     * @param [type] $filled
     * @return Render
     */
    private function render ($filled = null, $block = 0): AttributeViewCollection
    {
        return (new AttributeViewCollection($this->config, $this->collection, $filled, $this->extra))->enableBundleMode($block);
    }

    // --------------------------------------------------------------
    // Генераторы HTML
    // --------------------------------------------------------------

    public function showAttributeById(string|int $id, null|int $block = null)
    {
        return $this->showAttribute($this->collection->get($id), $block);
    }

    public function showAttribute(Attribute $attribute, null|int $block = null)
    {
        $template = $this->config['template_items_prefix'] . $attribute->viewTemplate;
        $extra = $this->extra;
        $alias = $aliasName = $this->config['alias'];

        if (!isset($block)) {
            $block = 0;
        }

        $alias .= '[' . $block . ']';
        $params = [
            'attribute' => $attribute,
            'alias'     => $alias,
            'aliasName' => $aliasName,
            'block'     => $block,
            'extra'     => $extra,
        ];
        $content = (string)view($template, $params);

        if (isset($this->config['template_item'])) {
            $content = (string)view($this->config['template_item'], $params + ['content' => $content]);
        }

        return $content;
    }

    // --------------------------------------------------------------
    // Options
    // --------------------------------------------------------------

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
