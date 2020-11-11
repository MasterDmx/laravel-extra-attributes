<?php

namespace MasterDmx\LaravelExtraAttributes\View;

use MasterDmx\LaravelExtraAttributes\Entities\Bundle;
use MasterDmx\LaravelExtraAttributes\Entities\Collection;

class BundleRender
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
     * Бандл заполненнех аттрибутов
     *
     * @var \MasterDmx\LaravelExtraAttributes\Entities\Bundle|null
     */
    private $filled;

    /**
     * Инициализатор ДО процесса инициализации
     *
     * @var \MasterDmx\LaravelExtraAttributes\View\initializer|null
     */
    private $initializer;

    /**
     * Доп. данные, передающиеся в шаблоны
     *
     * @var array
     */
    private $extra = [];

    /**
     * Инициализированные рендеры
     *
     * @var array
     */
    private $renders;

    public function __construct(array $config, Collection $collection = null, Bundle $filled = null, array $extra = null, Initializer $initializer = null)
    {
        $this->config = $config;
        $this->collection = $collection;
        $this->filled = $filled;
        $this->extra = $extra;
        $this->initializer = $initializer;

        if (!isset($this->filled)) {
            $this->filled = new Bundle();
        }
    }

    public function __toString()
    {
        return 'sdf';
    }

    /**
     * Отобразить UI
     *
     * @return string
     */
    public function ui(): string
    {
        return view($this->config['templates']['ui'], [
            'render' => $this
        ]);
    }

    public function block(int $number)
    {
        if (isset($this->renders[$number])) {
            return $this->rendera[$number];
        }

        return $this->rendera[$number] = $this->render($this->filled->getBlock($number), $number);
    }

    /**
     * Рендер
     *
     * @param [type] $filled
     * @return Render
     */
    private function render ($filled = null, $blockNumber = null): Render
    {
        $config = $this->config;

        if (isset($blockNumber)) {
            $config['name'] = $config['name'] . '[' . $blockNumber . ']';
        }

        return new Render($config, $this->collection, $filled, $this->initializer);
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
