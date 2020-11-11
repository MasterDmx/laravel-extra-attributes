<?php

namespace MasterDmx\LaravelExtraAttributes;

use App\Models\Product\Product;

class ExtraAttributesServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom( __DIR__.'/../config/attrubutes.php', 'attrubutes');

        $this->app->singleton(ExtraAttributesManager::class, function () {
            return new ExtraAttributesManager($this->app, config('attrubutes.contexts', []));
        });
    }
}
