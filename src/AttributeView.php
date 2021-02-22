<?php

namespace MasterDmx\LaravelExtraAttributes;

use Illuminate\Support\Arr;
use InvalidArgumentException;

class AttributeView
{
    const DEFAULT_UI                  = 'default';
    const DEFAULT_TEMPLATES_PREFIX    = 'attribute.';
    const DEFAULT_TEMPLATES_UI        = 'layout.main';
    const DEFAULT_TEMPLATES_GROUP     = null;
    const DEFAULT_BANDLE_BLOCKS_COUNT = 4;
    const OTHER_GROUP_ID              = 'undefined';
    const OTHER_GROUP_NAME            = 'Undefined';

    /**
     * Контекст
     *
     * @var Context
     */
    protected $context;

    /**
     * UI
     *
     * @var string
     */
    protected $ui = 'default';

    /**
     * Коллекция заполненных атрибутов
     *
     * @var null|Collection|Bundle
     */
    protected $filled;

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
     * Конфиг с сырыми данными
     *
     * @var array
     */
    protected $configRaw;

    /**
     * Конфиг
     *
     * @var array
     */
    protected $config;

    /**
     * Статус инициализации
     *
     * @var bool;
     */
    protected $initiated = false;

    public function __construct(AttributeContext $context, $ui)
    {
        $viewSettings = $context->views();

        if ($ui !== self::DEFAULT_UI && !isset($viewSettings[$ui])) {
            throw new InvalidArgumentException('ui "' . $ui . '" is not defined');
        }

        $this->context                         = $context;
        $this->ui                              = $ui;
        $this->configRaw                       = $viewSettings[$ui] ?? [];
        $this->config['ui']                    = $ui;
        $this->config['context_class']         = $context::class;
        $this->config['alias']                 = $context->alias;
        $this->config['mode']                  = $this->getProcessedConfigRawParam('mode', 'list');
        $this->config['template']              = $this->getProcessedConfigRawParam('template', null);
        $this->config['template_group']        = $this->getProcessedConfigRawParam('template_group', null);
        $this->config['template_item']         = $this->getProcessedConfigRawParam('template_item', null);
        $this->config['template_items_prefix'] = $this->getProcessedConfigRawParam('template_items_prefix', 'attributes.');

        // Добавляем доп. параметры
        $this->set($this->getProcessedConfigRawParam('extra', null));

        // Обработка групп
        foreach ($this->getProcessedConfigRawParam('groups', []) + [static::OTHER_GROUP_ID => static::OTHER_GROUP_NAME] as $key => $value) {
            $this->config['groups'][$key] = ['name' => $value];
        }
    }

    /**
     * Рендер вьюхи
     *
     * @return CollectionRender|BundleRender
     */
    public function render(): AttributeViewCollection|AttributeViewBundle
    {
        // Получение атрибутов контекста
        $attributes = $this->context->getAttributes();

        // Применяем пресет к аттрибутам, если он установлен
        if (isset($this->config['preset'])) {
            $attributes = $attributes->applyPreset($this->config['preset']);
        }

        // Обработка аттрибутов из конфига
        foreach ($this->getProcessedConfigRawParam('attributes', []) as $key => $value) {
            $id = is_numeric($key) ? $value : $key;

            if (!$attributes->has($id)) {
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
        foreach ($attributes->getIds() ?? [] as $id) {
            if (in_array($id, $this->config['attributes'] ?? [])) {
                continue;
            }

            $this->config['attributes'][] = $id;
            $this->config['groups'][static::OTHER_GROUP_ID]['ids'][] = $id;
        }

        // Выдача рендера бандлов
        if ($this->context->isBundle) {
            return new AttributeViewBundle($this->config, $attributes, $this->filled, $this->extra);
        }

        // Выдача рендера коллекций
        return new AttributeViewCollection($this->config, $attributes, $this->filled, $this->extra);
    }

    /**
     * Получить нужнное значение из сырого конфига с учетом приоритетности данных
     *
     * @param string $alias
     * @param mixed $default
     * @return mixed
     */
    public function getProcessedConfigRawParam($alias, $default = null)
    {
        if (isset($this->config['preset']) && isset($this->configRaw['presets'][$this->config['preset']][$alias])) {
            return $this->configRaw['presets'][$this->config['preset']][$alias];
        }

        if (isset($this->configRaw[$alias])) {
            return $this->configRaw[$alias];
        }

        return $default;
    }

    // -----------------------------------------------------------
    // Options
    // -----------------------------------------------------------

    /**
     * Задать пресет
     *
     * @param int|string $preset
     * @return static
     */
    public function setPreset($preset)
    {
        // Указываем пресет в конфиге, чтобы потом передать его в рендеры
        $this->config['preset'] = $preset;

        return $this;
    }

    /**
     * Задать заполненные данные
     *
     * @param null|array|AttributeBundle|AttributeCollection $data
     * @return static
     */
    public function fill($data)
    {
        if (isset($data)) {
            if (is_array($data)) {
                $data = $this->context->isBundle ? $this->context->newBundle($data, true) : $this->context->newCollection($data, true);
            }

            if (is_a($data, AttributeBundle::class) || is_a($data, AttributeCollection::class) && $data->isNotEmpty()) {
                $this->filled = $data;
            }
        }

        return $this;
    }

    /**
     * Установить доп. данные
     *
     * @param string|int|array $alias
     * @param mixed $value
     * @return static
     */
    public function set($alias = null, $value = null)
    {
        if (!empty($alias)) {
            if (is_array($alias)) {
                foreach ($alias as $key => $value) {
                    $this->set($key, $value);
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
