<?php

namespace App\Handler;

/**
 * Защита от рекурсии при двусторонней синхронизации Заявок и Сделок.
 *
 * Обработчик заявки обновляет сделку, что порождает событие сделки, а его
 * обработчик обновляет заявку и так далее. Флаг блокировки разрывает цикл:
 * на время записи в смежную сущность встречный обработчик пропускается.
 *
 * @package App\Handler
 */
class SyncGuard
{
    /** @var bool идёт ли сейчас синхронизация */
    private static bool $locked = false;

    /**
     * Сообщает, выполняется ли синхронизация в данный момент.
     *
     * @return bool
     */
    public static function isLocked(): bool
    {
        return self::$locked;
    }

    /**
     * Включает блокировку перед записью в смежную сущность.
     *
     * @return void
     */
    public static function lock(): void
    {
        self::$locked = true;
    }

    /**
     * Снимает блокировку после завершения записи.
     *
     * @return void
     */
    public static function unlock(): void
    {
        self::$locked = false;
    }
}
