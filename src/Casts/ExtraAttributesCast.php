<?php

namespace MasterDmx\LaravelExtraAttributes\Casts;

use ErrorException;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use MasterDmx\LaravelExtraAttributes\Entities\AttributeCollection;
use MasterDmx\LaravelExtraAttributes\ExtraAttributesManager;

class ExtraAttributesCast implements CastsAttributes
{
    /**
     * Менеджер
     *
     * @var ExtraAttributesManager
     */
    private $manager;

    public function __construct()
    {
        $this->manager = app(ExtraAttributesManager::class);
    }

    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return array
     */
    public function get($model, $key, $value, $attributes)
    {
        return $this->manager->get(get_class($model), json_decode($value, true), true, false);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  array|string|\MasterDmx\LaravelExtraAttributes\Entities\AttributeCollection $value
     * @param  array  $attributes
     * @return string
     */
    public function set($model, $key, $value, $attributes)
    {
        if (is_a($value, AttributeCollection::class)) {
            return json_encode($value->export());
        }

        if (is_array($value)) {
            return json_encode($this->manager->get(get_class($model), $this->manager->clearInputData($value), true, true)->export());
        }

        throw new ErrorException('Undefined value type');
    }
}
