<?php

namespace MasterDmx\LaravelExtraAttributes\View;

use MasterDmx\LaravelExtraAttributes\Entities\Collection;

class CollectionRender
{
    /**
     * Конфиг
     *
     * @var array
     */
    private $config;

    /**
     * Коллекция аттрибутов
     *
     * @param \MasterDmx\LaravelExtraAttributes\Entities\Collection|null
     */
    private $collection;

    public function __construct(array $config, Collection $collection = null)
    {
        $this->config = $config;
        $this->collection = $collection;
    }

    public function __toString()
    {
        return $this->show();
    }

    public function show()
    {
        return view($this->config['templates']['main']);
    }

    /**
     * Показ списка
     *
     * @return string
     */
    public function showList(): string
    {
        $list = [];

        foreach ($this->config['attributes'] as $id) {
            $list[] = $this->attribute($id);
        }

        return $list ? implode('', $list) : '';
    }

    /**
     * Показ списка по группам
     *
     * @return string
     */
    public function showListByGroups(): string
    {
        $content = [];

        foreach ($this->config['groups'] as $groupId => $group) {
            if (empty($group['ids'])) {
                continue;
            }

            $list = [];

            foreach ($group['ids'] as $id) {
                $list[] = $this->attribute($id);
            }

            if ($this->config['templates']['group'] ?? false) {
                $content[] = view($this->config['templates']['group'], [
                    'name' => $group['name'] ?? 'Без имени',
                    'content' => implode('', $list)
                ]);
            } else {
                foreach ($list as $item) {
                    $content[] = $item;
                }
            }
        }

        return $content ? implode('', $content) : '';
    }

    /**
     * Отрисовать аттрибут по ID
     *
     * @param string|int|float $id
     * @return string
     */
    public function attribute($id): string
    {
        $attribute = $this->collection->get($id);
        $class = get_class($attribute);
        $template = $this->config['templates']['entities'][$attribute->entity];
        $content = view($template, [
            'attribute' => $attribute,
            'class' => $class,
            'name' => $this->config['name'] ?? 'undefined'
        ]);

        if (isset($this->config['templates']['attribute'])) {
            return view($this->config['templates']['attribute'], [
                'content' => $content,
                'attribute' => $attribute,
            ]);
        }

        return $content;
    }
}
