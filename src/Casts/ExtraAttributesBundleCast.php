<?php

namespace MasterDmx\LaravelExtraAttributes\Casts;

use ErrorException;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use MasterDmx\LaravelExtraAttributes\Entities\Bundle;
use MasterDmx\LaravelExtraAttributes\ExtraAttributesManager;

class ExtraAttributesBundleCast extends ExtraAttributesCast implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return $this->manager->bundle($this->getAliasByModel($model), json_decode($value, true), true, false);
    }

    public function set($model, $key, $value, $attributes)
    {
        if (is_a($value, Bundle::class)) {
            return json_encode($value->export());
        }

        if (is_array($value)) {
            return json_encode($this->manager->bundle($this->getAliasByModel($model), $this->manager->clearInputData($value), true, true)->export());
        }

        throw new ErrorException('Undefined value type');
    }
}
