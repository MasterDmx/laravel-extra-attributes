<?php

namespace MasterDmx\LaravelExtraAttributes;

use Illuminate\Support\Str;

/**
 * Аттрибут
 *
 * @version 1.0.1 2020-11-22
 */
abstract class Attribute
{
    const DATA_KEY_STRICT_COMPARISON = 'strict';

    /**
     * ID
     *
     * @var string
     */
    public $id;

    /**
     * View шаблон
     *
     * @var string
     */
    public $viewTemplate;

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

    /**
     * Важный аттрибут (режим строго сравнения)
     *
     * @var bool
     */
    public $strict = false;

    /**
     * Доступен для сравнения
     *
     * @var bool
     */
    public $сompareAvailable;

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
        $this->id               = $properties['id'];
        $this->viewTemplate     = $properties['view'] ?? $this->getViewTemplateByHandlerName();
        $this->name             = $properties['name'];
        $this->presets          = $properties['presets'] ?? null;
        $this->сompareAvailable = (bool)($properties['compare'] ?? true);
    }

    /**
     * Импорт значений
     *
     * @param array|int|string|double|float $data
     * @return void
     */
    public function import($data): void
    {
        if (isset($data[static::DATA_KEY_STRICT_COMPARISON])) {
            $this->strict = (bool)$data[static::DATA_KEY_STRICT_COMPARISON];
        }
    }

    /**
     * Экспорт значений
     *
     * @return array|int|string|double|float
     */
    public function export()
    {
        return ($this->strict ?? null) ? [static::DATA_KEY_STRICT_COMPARISON => true] : [];
    }

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
     * Имеет значения (влияет на пропуск значений при записи в бд)
     *
     * @return boolean
     */
    public function hasValues(): bool
    {
        return true;
    }

    /**
     * Имеет значения для сравнения
     *
     * @return boolean
     */
    public function hasValuesForComparison(): bool
    {
        return $this->hasValues();
    }

    /**
     * Сравнение с другим полем
     *
     * @return bool
     */
    public function compare($attribute): bool
    {
        return true;
    }

    /**
     * Изменить данные по пресету
     *
     * @param $data
     * @return void
     */
    protected function changeUnderPreset(array $data): void
    {
        if (isset($data['name'])) {
            $this->name = $data['name'];
        }
    }

    /**
     * Получить название view шаблона по названию класса-обработчика
     *
     * @return string
     */
    protected function getViewTemplateByHandlerName(): string
    {
        $result = Str::snake(class_basename($this));

        if (substr($result, -10) === '_attribute') {
            $result = substr($result, 0, -10);
        }

        return $result;
    }
}
