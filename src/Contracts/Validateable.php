<?php

namespace MasterDmx\LaravelExtraAttributes\Contracts;


interface Validateable
{
    /**
     * Метод проверяет сырые данные перед насыщением значение.
     * * true - данные верны, можно насыщать
     * * false - данные не верны. Объект будет исключен из коллекции
     *
     * @param  $data
     * @return bool
     */
    public function isValidRaw($data): bool;

    /**
     * Метод проверяет данные уже после насыщения
     *
     * @param  $data
     * @return bool
     */
    public function isValid(): bool;

    /**
     * Импорт сырых значений + их обработка
     * * Метод будет использован вместе метода чистого импорта import()
     *
     * @param array|int|string|double|float $data
     * @return void
     */
    public function importRaw($data): void;
}
