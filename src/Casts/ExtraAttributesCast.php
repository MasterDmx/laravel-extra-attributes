<?php

namespace MasterDmx\LaravelExtraAttributes\Casts;

use ErrorException;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use MasterDmx\LaravelExtraAttributes\AttributeBundle;
use MasterDmx\LaravelExtraAttributes\AttributeCollection;
use MasterDmx\LaravelExtraAttributes\AttributeManager;

final class ExtraAttributesCast implements CastsAttributes
{
    /**
     * Контекст
     *
     * @var Context
     */
    protected $context;

    public function __construct(string $contextClass)
    {
        $this->context = app(AttributeManager::class)->getContext($contextClass);
    }

    public function get($model, $key, $value, $attributes)
    {
        return $this->context->isBundle ? $this->context->newBundle(json_decode($value, true), false) : $this->context->newCollection(json_decode($value, true), false);
    }

    public function set($model, $key, $value, $attributes)
    {
        if (is_a($value, AttributeCollection::class) || is_a($value, AttributeBundle::class)) {
            return json_encode($value->export());
        }

        if (is_array($value)) {
            return json_encode($this->context->isBundle ? $this->context->newBundle($value, false)->export() : $this->context->newCollection($value, false)->export());
        }

        throw new ErrorException('Undefined value type');
    }
}
