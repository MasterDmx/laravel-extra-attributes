<?php

namespace MasterDmx\LaravelExtraAttributes\Entities;

abstract class Attribute
{
    /**
     * ID
     *
     * @var string
     */
    public $id;

    /**
     * Название
     *
     * @var string
     */
    public $name;

    /**
     * Пресеты
     *
     * @var array|null
     */
    public $presets;

    public function __construct(array $handbook)
    {
        $this->init($handbook);
    }

    /**
     * Инициализация атрибута по справочнику
     *
     * @param array $data
     * @return void
     */
    protected function init(array $properties)
    {
        $this->id = $properties['id'];
        $this->name = $properties['name'];
        $this->presets = $properties['presets'] ?? null;
    }

    /**
     * Импорт значений
     *
     * @param array|int|string|double|float $data
     * @return void
     */
    abstract public function import($data);

    /**
     * Экспорт значений
     *
     * @return array|int|string|double|float
     */
    abstract public function export();

    /**
     * Проверить наличие пресета
     *
     * @param string $id ID пресета
     * @return boolean
     */
    public function hasPreset(string $id): bool
    {
        return !isset($this->presets) || (isset($this->presets[$id]) || in_array($id, $this->presets));
    }

    /**
     * Применить пресет, если он заменяет данные
     *
     * @param string $id
     * @return self
     */
    public function applyPreset(string $id): self
    {
        if (is_array($this->presets[$id] ?? null)) {
            $this->changeUnderPreset($this->presets[$id]);
        }

        return $this;
    }

    /**
     * Проверка на пустоту
     *
     * @return bool
     */
    public function checkForEmpty(): bool
    {
        return true;
    }

    /**
     * Изменить данные по пресету
     *
     * @param $data
     * @return self
     */
    protected function changeUnderPreset(array $data): void
    {
        if (isset($data['name'])) {
            $this->name = $data['name'];
        }
    }
}
