<?php

namespace MasterDmx\LaravelExtraAttributes;

use Illuminate\Support\ServiceProvider;

class AttributeServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom( __DIR__.'/../config/attrubutes.php', 'attrubutes');

        $this->app->singleton(AttributeManager::class);
    }
}
