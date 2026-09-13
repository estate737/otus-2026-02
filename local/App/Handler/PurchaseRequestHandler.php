<?php

namespace App\Handler;

use App\Service\PurchaseService;
use Bitrix\Crm\Item;
use Bitrix\Main\Event;

/**
 * Обработка заявок на закупку по событиям смарт-процесса.
 *
 * После сохранения элемента ядро CRM отправляет события onCrmDynamicItemAdd_<ID>
 * и onCrmDynamicItemUpdate_<ID>. Заявка из карточки, канбана, REST или кода проходит
 * один и тот же путь: при создании назначается согласующий, при переводе в финальную
 * стадию выполняется решение закупщика.
 *
 * @package App\Handler
 */
class PurchaseRequestHandler
{
    /** @var bool идёт ли запись заявки самим обработчиком */
    private static bool $locked = false;

    /**
     * Обработчик события создания заявки.
     *
     * @param Event $event событие crm:onCrmDynamicItemAdd_<ID>
     * @return void
     */
    public static function onAdd(Event $event): void
    {
        $item = self::extractItem($event);
        if ($item !== null) {
            self::run(static fn (PurchaseService $service) => $service->prepareNewRequest($item));
        }
    }

    /**
     * Обработчик события изменения заявки.
     *
     * @param Event $event событие crm:onCrmDynamicItemUpdate_<ID>
     * @return void
     */
    public static function onUpdate(Event $event): void
    {
        $item = self::extractItem($event);
        if ($item !== null) {
            self::run(static fn (PurchaseService $service) => $service->processDecision($item));
        }
    }

    /**
     * Возвращает заявку из события, если её сохраняет не сам обработчик.
     *
     * @param Event $event событие смарт-процесса
     * @return Item|null
     */
    private static function extractItem(Event $event): ?Item
    {
        if (self::$locked) {
            return null;
        }

        $item = $event->getParameter('item');

        return $item instanceof Item ? $item : null;
    }

    /**
     * Выполняет действие с блокировкой повторного входа.
     *
     * Сервис сохраняет заявку, что снова порождает событие изменения;
     * блокировка не даёт обработчику зациклиться.
     *
     * @param callable $action действие над сервисом заявок
     * @return void
     */
    private static function run(callable $action): void
    {
        self::$locked = true;

        try {
            $action(new PurchaseService());
        } finally {
            self::$locked = false;
        }
    }
}
