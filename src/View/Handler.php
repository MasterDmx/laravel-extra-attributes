<?php

namespace MasterDmx\LaravelExtraAttributes\View;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use MasterDmx\LaravelExtraAttributes\Entities\AttributeCollection;
use MasterDmx\LaravelExtraAttributes\Entities\Context;

class Handler
{
    const DEFAULT_TEMPLATES_PREFIX = 'attribute.';
    const DEFAULT_TEMPLATES_MAIN = 'layout.main';
    const DEFAULT_TEMPLATES_GROUP = 'layout.group';
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
     * Коллекция атрибутов для заполнения
     *
     * @var null|\MasterDmx\LaravelExtraAttributes\Entities\AttributeCollection
     */
    protected $fillAttributes;

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
     * Аттрибуты
     *
     * @var \MasterDmx\LaravelExtraAttributes\Entities\AttributeCollection
     */
    protected $attributes;

    /**
     * Обработанный конфиг
     *
     * @var array
     */
    protected $config;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function __toString()
    {
        return (string)$this->show();
    }

    // -----------------------------------------------------------
    // Show
    // -----------------------------------------------------------

    /**
     * Построить View
     *
     * @return string
     */
    public function show()
    {
        $config = $this->temp['config'] = $this->context->views();

        if ($config['bundle'] ?? false) {
            debug_print('Режим бандла');
        } else {
            debug_print('Режим обычной коллекции');
        }

        // Шаблоны
        $this->config['templates']['main'] = $this->getConfigItem('templates.main', static::DEFAULT_TEMPLATES_MAIN);
        $this->config['templates']['group'] = $this->getConfigItem('templates.group', static::DEFAULT_TEMPLATES_GROUP);
        $this->config['templates']['entities'] = $this->padConfigItem('templates.entities', []);

        // Группы
        foreach ($this->padConfigItem('groups', []) + [static::OTHER_GROUP_ID => static::OTHER_GROUP_NAME] as $key => $value) {
            $this->config['groups'][$key] = ['name' => $value];
        }

        // Обработка аттрибутов из конфига
        foreach ($this->getConfigItem('attributes', []) as $key => $value) {
            $id = is_numeric($key) ? $value : $key;

            if (!$this->context->getAttributes()->has($id)) {
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
        foreach ($this->context->getAttributes()->getIds() ?? [] as $id) {
            if (in_array($id, $this->config['attributes'])) {
                continue;
            }

            $this->config['attributes'][] = $id;
            $this->config['groups'][static::OTHER_GROUP_ID]['ids'][] = $id;
        }

        return view($this->config['templates']['main'], [
            'builder' => $this
        ]);
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
            $list[] = $this->renderAttributeById($id);
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
                $list[] = $this->renderAttributeById($id);
            }

            if ($this->config['use_group_template']) {
                $content[] = view($this->config['use_group_template'], [
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

    public function showTemplate()
    {
        return view('attribute.filters.main');
    }

    /**
     * Отрисовать аттрибут по ID
     *
     * @param string|int|float $id
     * @return string
     */
    public function renderAttributeById($id): string
    {
        $attribute = $this->attributes->get($id);
        $class = get_class($attribute);
        $template = $this->config['templates'][$class];

        return view($template, [
            'attribute' => $attribute,
            'class' => $class
        ]);
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

    // -----------------------------------------------------------
    // Options
    // -----------------------------------------------------------

    /**
     * Установить пресет
     *
     * @param string $name Название пресета
     * @return self
     */
    public function preset(string $name): self
    {
        $this->options['preset'] = $name;
        return $this;
    }

    /**
     * Пользовательский интерфейс
     *
     * @param string $name Название пресета
     * @return self
     */
    public function ui(string $name): self
    {
        $this->options['ui'] = $name;
        return $this;
    }

    /**
     * Пользовательский интерфейс
     *
     * @param string $name Название пресета
     * @return self
     */
    public function bundle($bundle): self
    {
        debug_print('Есть бандл на вход');

        $this->temp['bundle'] = $bundle;
        return $this;
    }

    /**
     * Заполнить данными
     *
     * @param array|object $data
     * @param boolean $intersect
     * @return self
     */
    public function fill($data, bool $intersect = false): self
    {
        if (isset($data)) {
            if (is_array($data)) {
                $data = $this->context->createCollection($data);
            }

            if (is_a($data, AttributeCollection::class)) {
                $this->fillAttributes = $data;
            }
        }

        return $this;
    }

}
