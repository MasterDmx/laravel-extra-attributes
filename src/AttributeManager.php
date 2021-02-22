<?php

namespace MasterDmx\LaravelExtraAttributes;

use Illuminate\Container\EntryNotFoundException;

class AttributeManager
{
    /**
     * Получить инстанс контекста
     */
    public function getContext(string $class): AttributeContext
    {
        try {
            return app($class);
        } catch (EntryNotFoundException $th) {
            throw new EntryNotFoundException ('Context ' . $class . ' not found');
        }
    }

    public function newCollection(string $contextClass, array $import = null, bool $validate = false): AttributeCollection
    {
        return $this->getContext($contextClass)->newCollection($import, $validate);
    }

    public function newBundle(string $contextClass, array $import = null, bool $validate = false): AttributeBundle
    {
        return $this->getContext($contextClass)->newBundle($import, $validate);
    }

    /**
     * Сформировать пользовательский интерфейс
     *
     * @param string $alias Псевдоним контекста
     * @param mixed $fill Коллекция атрибутов для заполнения
     * @return AttributeView
     */
    public function view(string $contextClass, string $ui = 'default'): AttributeView
    {
        return new AttributeView($this->getContext($contextClass), $ui);
    }

    // -------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------

    public function clearInputData(array $data)
    {
        $new = [];

        foreach ($data as $key => $elem) {
            if (is_array($elem)) {
                $elem = $this->clearInputData($elem);
            }

            if (empty($elem) && $elem != '0') {
                continue;
            }

            $new[$key] = $elem;
        }

        return $new;
    }
}
