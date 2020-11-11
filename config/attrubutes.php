<?php

return [
    // Регистрация контекстов
    'contexts' => [],

    // Регистрация сущностей
    'entities' => [
        'interval'    => MasterDmx\LaravelExtraAttributes\Entities\Attributes\IntervalAttribute::class,
        'list'        => MasterDmx\LaravelExtraAttributes\Entities\Attributes\ListAttribute::class,
        'string'      => MasterDmx\LaravelExtraAttributes\Entities\Attributes\StringAttribute::class,
        'string_list' => MasterDmx\LaravelExtraAttributes\Entities\Attributes\StringListAttribute::class,
    ],

    // Класс пустышка
    'stub' => MasterDmx\LaravelExtraAttributes\Entities\AttributeStub::class,

    // Ключ аттрибутов в БД
    'name' => 'extra_attributes',
];
