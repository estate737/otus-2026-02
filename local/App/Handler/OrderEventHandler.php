<?php

namespace App\Handler;

use App\Service\OrderDealSync;
use Bitrix\Main\Loader;

/**
 * Обработчики событий элементов инфоблока «Заявки».
 *
 * При создании и изменении заявки переносят сумму и ответственного в связанную
 * сделку. При удалении заявки сделка сохраняется (это самостоятельная запись
 * CRM), обработчик лишь корректно отрабатывает событие.
 *
 * @package App\Handler
 */
class OrderEventHandler
{
    /**
     * Обработчик события OnAfterIBlockElementAdd.
     *
     * @param array $arFields поля добавленного элемента
     * @return void
     */
    public static function onAfterAdd($arFields): void
    {
        self::handleSave($arFields);
    }

    /**
     * Обработчик события OnAfterIBlockElementUpdate.
     *
     * @param array $arFields поля изменённого элемента
     * @return void
     */
    public static function onAfterUpdate($arFields): void
    {
        self::handleSave($arFields);
    }

    /**
     * Обработчик события OnBeforeIBlockElementDelete.
     *
     * Удаление заявки не затрагивает сделку: связанная сделка остаётся в CRM.
     *
     * @param int $elementId ID удаляемого элемента
     * @return void
     */
    public static function onBeforeDelete($elementId): void
    {
        // Намеренно без действий над сделкой: заявка ведёт сделку, но не владеет ею.
    }

    /**
     * Общая логика создания и изменения: синхронизация заявки со сделкой.
     *
     * @param array $arFields поля элемента (ожидаются ID и IBLOCK_ID)
     * @return void
     */
    private static function handleSave($arFields): void
    {
        if (SyncGuard::isLocked() || !is_array($arFields))
        {
            return;
        }

        $elementId = (int) ($arFields['ID'] ?? 0);
        if ($elementId <= 0)
        {
            return;
        }

        if (!Loader::includeModule('iblock') || !Loader::includeModule('crm'))
        {
            return;
        }

        $sync = new OrderDealSync();

        $iblockId = (int) ($arFields['IBLOCK_ID'] ?? 0);
        if ($iblockId > 0 && $iblockId !== $sync->getIblockId())
        {
            return;
        }

        SyncGuard::lock();
        try
        {
            $sync->syncOrderToDeal($elementId);
        }
        catch (\Throwable $e)
        {
            // Ошибка синхронизации не должна прерывать сохранение элемента.
        }
        finally
        {
            SyncGuard::unlock();
        }
    }
}
