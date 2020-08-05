<?php

namespace MasterDmx\LaravelExtraAttributes;

use App\Models\Product\Product;

class ExtraAttributesServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        // Инициализация менеджера
        $this->app->singleton(ExtraAttributesManager::class, function () {
            return new ExtraAttributesManager($this->app);
        });

        // Инициализация контекстов
        foreach (config('attrubutes.contexts', []) as $alias => $class) {
            $this->app->singleton(ExtraAttributesManager::getContextAliasForContainer($alias), function () use ($class) {
                return new $class();
            });
        }
    }
}
