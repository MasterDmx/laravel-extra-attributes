<?php

namespace MasterDmx\LaravelExtraAttributes\Casts;

use ErrorException;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use MasterDmx\LaravelExtraAttributes\ExtraAttributesManager;

abstract class ExtraAttributesCast implements CastsAttributes
{
    /**
     * Менеджер
     *
     * @var ExtraAttributesManager
     */
    protected $manager;

    /**
     * Алиас контекста
     *
     * @var string|null
     */
    protected $alias;

    public function __construct(string $alias = null)
    {
        $this->alias = $alias;
        $this->manager = app(ExtraAttributesManager::class);
    }

    abstract public function get($model, $key, $value, $attributes);

    abstract public function set($model, $key, $value, $attributes);

    protected function getAliasByModel($model)
    {
        return isset($this->alias) ? $this->alias : get_class($model);
    }
}
