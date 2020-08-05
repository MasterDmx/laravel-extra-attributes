<?php

namespace MasterDmx\LaravelExtraAttributes\Trates;

use MasterDmx\LaravelExtraAttributes\Manager as Attributes;

trait ExtraAttributable
{
    private $extraAttributesIdentityMap;

    public function extraAttributable()
    {
        return [
            'context' => __CLASS__
        ];
    }

    public function getExtraAttributesAttribute($value)
    {
        if (isset($this->extraAttributesIdentityMap['__'])) {
            return $this->extraAttributesIdentityMap['__'];
        }

        return $this->extraAttributesIdentityMap['__'] = app(Attributes::class)->map(json_decode($value, true), $this->extraAttributable()['context']);
    }


}
