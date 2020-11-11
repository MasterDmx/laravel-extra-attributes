<?php

namespace MasterDmx\LaravelExtraAttributes\View;

use InvalidArgumentException;
use MasterDmx\LaravelExtraAttributes\Entities\AttributeCollection;
use MasterDmx\LaravelExtraAttributes\Entities\Context;

class Handler
{
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
     * @var array
     */
    protected $options;

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
        // Обработка коллекции атрибутов
        $this->processAttributes();

        // Обработка конфига
        $this->processConfig();

        $method = $this->config['showMethod'];
        // $method = 'showTemplate';

        return view('attribute.filters.main', [
            'builder' => $this
        ]);

        return $this->$method();
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
    // Processing
    // -----------------------------------------------------------

    /**
     * Обработка коллекции атрибутов
     *
     * @return void
     */
    public function processAttributes(): void
    {
        $this->attributes = $this->context->getAttributes()->clone();

        if (isset($this->fillAttributes)) {
            $this->attributes = $this->attributes->replaceAttributesFrom($this->fillAttributes);
        }

        if (isset($this->options['preset'])) {
            $this->attributes = $this->attributes->applyPreset($this->options['preset']);
        }

        // debug_print($this->attributes);
    }

    /**
     * Обработка конфигураций контекста
     *
     * @return void
     */
    public function processConfig(): void
    {
        $config = $this->context->views();
        $replacedConfig = $config['preset/' . ($this->options['preset'] ?? '')] ?? [];

        // Шаблоны
        $this->config['templates'] = $config['templates'] ?? [];

        foreach ($replacedConfig['templates'] ?? [] as $key => $value) {
            $this->config['templates'][$key] = $value;
        }

        // Общие группы
        foreach ($config['groups'] ?? [] as $id => $name) {
            $this->config['groups'][$id] = ['name' => $name];
        }

        // Группы пресета
        foreach ($replacedConfig['groups'] ?? [] as $id => $name) {
            $this->config['groups'][$id] = ['name' => $name];
        }

        // Группа по умолчанию
        $this->config['groups'][static::OTHER_GROUP_ID] = ['name' => static::OTHER_GROUP_NAME];

        // Отдельный шаблон группы
        $this->config['use_group_template'] = $replacedConfig['use_group_template'] ?? $config['use_group_template'] ?? null;

        // Метод показа
        $this->config['show'] = $replacedConfig['show'] ?? $config['show'] ?? 'list';
        $this->config['showMethod'] = 'show' . ucfirst($this->config['show']);

        // Контент
        $this->config['attributes'] = [];

        // Обработка аттрибутов из конфига
        foreach ($replacedConfig['attributes'] ?? $config['attributes'] ?? [] as $key => $value) {
            $id = is_numeric($key) ? $value : $key;

            if (!$this->attributes->has($id)) {
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
        foreach ($this->attributes->getIds() ?? [] as $id) {
            if (in_array($id, $this->config['attributes'])) {
                continue;
            }

            $this->config['attributes'][] = $id;
            $this->config['groups'][static::OTHER_GROUP_ID]['ids'][] = $id;
        }
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
    public function bundle($content): self
    {
        debug_print('Есть бандл на вход');
        // $this->options['ui'] = $name;
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
