<?php

namespace MasterDmx\LaravelExtraAttributes\View;

use Illuminate\Support\Arr;
use MasterDmx\LaravelExtraAttributes\Entities\Bundle;
use MasterDmx\LaravelExtraAttributes\Entities\Collection;
use MasterDmx\LaravelExtraAttributes\Entities\Context;

class Initializer
{
    const DEFAULT_NAME = 'extra_attributes';
    const DEFAULT_TEMPLATES_PREFIX = 'attribute.';
    const DEFAULT_TEMPLATES_UI = 'layout.main';
    const DEFAULT_TEMPLATES_GROUP = null;
    const DEFAULT_BANDLE_BLOCKS_COUNT = 4;
    const OTHER_GROUP_ID = 'undefined';
    const OTHER_GROUP_NAME = 'Undefined';

    /**
     * Текущий контекст
     *
     * @var \MasterDmx\LaravelExtraAttributes\Entities\Context
     */
    protected $context;

    /**
     * Коллекция заполненных атрибутов
     *
     * @var null|\MasterDmx\LaravelExtraAttributes\Entities\Collection
     */
    protected $filledCollection;

    /**
     * Бандл заполненных аттрибутов
     *
     * @var null|\MasterDmx\LaravelExtraAttributes\Entities\Bundle
     */
    protected $filledBundle;

    /**
     * Параметры
     *
     * @var array|null
     */
    protected $options;

    /**
     * Временные данные
     *
     * @var array|null
     */
    protected $temp;

    /**
     * Доп. данные, передающиеся в шаблоны
     *
     * @var array
     */
    private $extra = [];

    /**
     * Обработанный конфиг
     *
     * @var array
     */
    protected $config;

    public function __construct(Context $context)
    {
        $this->context = $context;
        $this->temp['config'] = $this->context->views();
    }

    // -----------------------------------------------------------
    // Show
    // -----------------------------------------------------------

    /**
     * Построить View
     *
     * @return Render|BundleRender
     */
    public function init()
    {
        // Тут необходимо клонировать текущий объект

        // Шаблоны
        $this->config['name'] = config('attrubutes.name', static::DEFAULT_NAME);
        $this->config['templates']['ui'] = $this->getConfigItem('templates.ui', static::DEFAULT_TEMPLATES_UI);
        $this->config['templates']['group'] = $this->getConfigItem('templates.group', static::DEFAULT_TEMPLATES_GROUP);
        $this->config['templates']['attribute'] = $this->getConfigItem('templates.attribute', null);
        $this->config['templates']['entities'] = $this->padConfigItem('templates.entities', []);
        $this->config['bundle'] = $this->getConfigItem('bundle', false);


        // Группы
        foreach ($this->padConfigItem('groups', []) + [static::OTHER_GROUP_ID => static::OTHER_GROUP_NAME] as $key => $value) {
            $this->config['groups'][$key] = ['name' => $value];
        }

        // Работа с аттрибутами контекста
        $collection = $this->context->getAttributes();

        if (isset($this->options['preset'])) {
            $collection = $collection->applyPreset($this->options['preset']);
        }

        // Обработка аттрибутов из конфига
        foreach ($this->getConfigItem('attributes', []) as $key => $value) {
            $id = is_numeric($key) ? $value : $key;

            if (!$collection->has($id)) {
                continue;
            }

            $this->config['attributes'][] = $id;

            if (is_numeric($key)) {
                $this->config['groups'][static::OTHER_GROUP_ID]['ids'][] = $value;
            } elseif (is_string($value)) {
                $this->config['groups'][$value]['ids'][] = $key;
            } elseif (is_array($value)) {
                $group = $value['group'] ?? static::OTHER_GROUP_ID;
                $this->config['groups'][$group]['ids'][] = $key;
            }
        }

        // Обработка оставшихся полей в контексте
        foreach ($collection->getIds() ?? [] as $id) {
            if (in_array($id, $this->config['attributes'])) {
                continue;
            }

            $this->config['attributes'][] = $id;
            $this->config['groups'][static::OTHER_GROUP_ID]['ids'][] = $id;
        }

        if ($this->config['bundle']) {
            return new BundleRender($this->config, $collection, $this->filledBundle, $this->extra);
        }

        return new Render($this->config, $collection, $this->filledCollection, $this->extra);
    }

    public function renderAttribute($id, array $replaceOptions = [])
    {
        $config['name'] = config('attrubutes.name', static::DEFAULT_NAME);
        $config['templates']['entities'] = $this->padConfigItem('templates.entities', []);
        $config['templates']['attribute'] = $this->getConfigItem('templates.attribute');

        if (!empty($replaceOptions)) {
            foreach ($replaceOptions as $key => $value) {
                $config = $this->replaceParam($config, $key, $value);
            }
        }

        $collection = $this->context->getAttributes()->only($id);

        if (isset($this->options['preset'])) {
            $collection = $collection->applyPreset($this->options['preset']);
        }

        return (new Render($config, $collection, null, $this->extra))->showAttributeById($id);
    }

    // -----------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------

    /**
     * Получить параметр конфига с учетом приоритетности
     */
    private function getConfigItem(string $alias, $default = null)
    {
        $result = config('attrubutes.view.' . $alias, $default);

        if (Arr::has($this->temp['config'], $alias)) {
            $result = Arr::get($this->temp['config'], $alias);
        }

        if (Arr::has($this->temp['config'], 'presets.' . $this->options['preset'] . '.' . $alias)) {
            $result = Arr::get($this->temp['config'], 'presets.' . $this->options['preset'] . '.' . $alias);
        }

        return $result;
    }

    /**
     * Дополнить параметр конфига с учетом приоритетности (null значение удалит параметр)
     */
    private function padConfigItem(string $alias, $default = null)
    {
        $result = config('attrubutes.view.' . $alias, $default);

        if (Arr::has($this->temp['config'], $alias)) {
            foreach (Arr::get($this->temp['config'], $alias) as $key => $value) {
                if (!isset($value) && isset($result[$key])) {
                    unset($result[$key]);
                }

                $result[$key] = $value;
            }
        }

        if (Arr::has($this->temp['config'], 'presets.' . $this->options['preset'] . '.' . $alias)) {
            foreach (Arr::get($this->temp['config'], 'presets.' . $this->options['preset'] . '.' . $alias) as $key => $value) {
                if (!isset($value) && isset($result[$key])) {
                    unset($result[$key]);
                }

                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function replaceParam(array $data, $alias, $value = null)
    {
        Arr::set($data, $alias, $value);
        return $data;
    }

    // -----------------------------------------------------------
    // Options
    // -----------------------------------------------------------

    /**
     * Установить пресет
     *
     * @param string $name Название пресета
     * @return self
     */
    public function preset(string $name = null): self
    {
        if (!empty($name)) {
            $this->options['preset'] = $name;
        }

        return $this;
    }

    /**
     * Пользовательский интерфейс
     *
     * @param string $name Название пресета
     * @return self
     */
    public function bundle($data): self
    {
        if (isset($data)) {
            if (is_array($data)) {
                $data = $this->context->createBundle($data);
            }

            if (is_a($data, Bundle::class)) {
                $this->filledBundle = $data;
            }
        }

        return $this;
    }

    /**
     * Заполнить данными
     *
     * @param array|object $data
     * @return self
     */
    public function collection($data): self
    {
        if (isset($data)) {
            if (is_array($data)) {
                $data = $this->context->createCollection($data);
            }

            if (is_a($data, Collection::class)) {
                $this->filledCollection = $data;
            }
        }

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
