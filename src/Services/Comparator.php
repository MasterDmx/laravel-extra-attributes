<?php

namespace MasterDmx\LaravelExtraAttributes\Services;

use MasterDmx\LaravelExtraAttributes\Entities\Bundle;
use MasterDmx\LaravelExtraAttributes\Entities\Collection;

/**
 * Сервис сравнения аттрибутов
 *
 * @version 1.0.0 2020-11-22
 */
class Comparator
{
    /**
     * Сравнить аттрибуты с автоматическим вычислением коллекций и бандлов
     *
     * @param Bundle|Collection $entity1
     * @param Bundle|Collection $entity2
     * @return bool
     */
    public function compare($entity, $entity2, ?bool $strictly = null): bool
    {
        if (is_a($entity, Collection::class) && is_a($entity2, Collection::class)) {
            return $this->compareCollections($entity, $entity2, $strictly);
        }

        if (is_a($entity, Bundle::class) && is_a($entity2, Collection::class)) {
            return $this->compareBundleWithCollection($entity, $entity2, $strictly);
        }

        if (is_a($entity, Collection::class) && is_a($entity2, Bundle::class)) {
            return $this->compareCollectionWithBundle($entity, $entity2, $strictly);
        }

        if (is_a($entity, Bundle::class) && is_a($entity2, Bundle::class)) {
            return $this->compareBundles($entity, $entity2, $strictly);
        }

        return false;
    }

    /**
     * Сравнить бандлы
     *
     * @param Bundle $bundle
     * @param Bundle $bundle2
     * @param boolean|null $strictly
     * @return boolean
     */
    public function compareBundles(Bundle $bundle, Bundle $bundle2, ?bool $strictly = null): bool
    {
        foreach ($bundle as $innerCollection) {
            foreach ($bundle2 as $innerCollection2) {
                if ($this->compareCollections($innerCollection, $innerCollection2, $strictly)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Сравнить коллекцию с бандлом
     *
     * @param Bundle $bundle
     * @param Collection $collection
     * @param boolean|null $strictly
     *
     * @return boolean
     */
    public function compareCollectionWithBundle(Collection $collection, Bundle $bundle, ?bool $strictly = null): bool
    {
        foreach ($bundle as $innerCollection) {
            if ($this->compareCollections($collection, $innerCollection, $strictly)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Сравнить бандл с коллекцией
     *
     * @param Bundle $bundle
     * @param Collection $collection
     * @param boolean|null $strictly
     *
     * @return boolean
     */
    public function compareBundleWithCollection(Bundle $bundle, Collection $collection, ?bool $strictly = null): bool
    {
        foreach ($bundle as $innerCollection) {
            if ($this->compareCollections($innerCollection, $collection, $strictly)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Сравнить коллекции
     *
     * @param Collection $collection
     * @param Collection $collection2
     * @param boolean|null $strictly Режим строго сравнения
     *
     * @return boolean
     */
    public function compareCollections(Collection $collection, Collection $collection2, ?bool $strictly = null): bool
    {
        foreach ($collection as $key => $item) {
            if (!$item->сompareAvailable) {
                continue;
            }

            if ($collection2->has($key) && $collection2->get($key)->hasValuesForComparison()) {
                if (!$collection2->get($key)->compare($item)) {
                    return false;
                }
            } elseif (is_null($strictly) && $item->strict || is_bool($strictly) && $strictly) {
                return false;
            }
        }

        return true;
    }
}
