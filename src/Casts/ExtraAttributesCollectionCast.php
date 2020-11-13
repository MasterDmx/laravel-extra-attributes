<?php

namespace MasterDmx\LaravelExtraAttributes\Casts;

use ErrorException;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use MasterDmx\LaravelExtraAttributes\Entities\Collection;

class ExtraAttributesCollectionCast extends ExtraAttributesCast implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return $this->manager->collection($this->getAliasByModel($model), json_decode($value, true), true, false);
    }

    public function set($model, $key, $value, $attributes)
    {
        if (is_a($value, Collection::class)) {
            return json_encode($value->export());
        }

        if (is_array($value)) {
            return json_encode($this->manager->collection($this->getAliasByModel($model), $this->manager->clearInputData($value), true, true)->export());
        }

        throw new ErrorException('Undefined value type');
    }
}
