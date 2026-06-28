<?php

namespace App\Handler;

use App\Service\OrderDealSync;
use Bitrix\Main\Loader;

/**
 * Обработчики событий сделок CRM.
 *
 * При создании и изменении сделки переносят её сумму и ответственного во все
 * связанные заявки. При удалении сделки снимают ссылку на неё у заявок, чтобы
 * не осталось битых связей.
 *
 * @package App\Handler
 */
class DealEventHandler
{
    /**
     * Обработчик события OnAfterCrmDealAdd.
     *
     * @param array $arFields поля добавленной сделки (содержат ID)
     * @return void
     */
    public static function onAfterAdd($arFields): void
    {
        self::syncToOrders($arFields);
    }

    /**
     * Обработчик события OnAfterCrmDealUpdate.
     *
     * @param array $arFields поля изменённой сделки (содержат ID)
     * @return void
     */
    public static function onAfterUpdate($arFields): void
    {
        self::syncToOrders($arFields);
    }

    /**
     * Обработчик события OnBeforeCrmDealDelete: отвязывает заявки от сделки.
     *
     * @param int $dealId ID удаляемой сделки
     * @return void
     */
    public static function onBeforeDelete($dealId): void
    {
        $dealId = (int) $dealId;
        if (SyncGuard::isLocked() || $dealId <= 0)
        {
            return;
        }

        if (!Loader::includeModule('iblock') || !Loader::includeModule('crm'))
        {
            return;
        }

        SyncGuard::lock();
        try
        {
            (new OrderDealSync())->unlinkOrdersFromDeal($dealId);
        }
        catch (\Throwable $e)
        {
            // Ошибка отвязки не должна прерывать удаление сделки.
        }
        finally
        {
            SyncGuard::unlock();
        }
    }

    /**
     * Общая логика создания и изменения: синхронизация сделки с заявками.
     *
     * @param array $arFields поля сделки (ожидается ID)
     * @return void
     */
    private static function syncToOrders($arFields): void
    {
        if (SyncGuard::isLocked() || !is_array($arFields))
        {
            return;
        }

        $dealId = (int) ($arFields['ID'] ?? 0);
        if ($dealId <= 0)
        {
            return;
        }

        if (!Loader::includeModule('iblock') || !Loader::includeModule('crm'))
        {
            return;
        }

        SyncGuard::lock();
        try
        {
            (new OrderDealSync())->syncDealToOrders($dealId);
        }
        catch (\Throwable $e)
        {
            // Ошибка синхронизации не должна прерывать сохранение сделки.
        }
        finally
        {
            SyncGuard::unlock();
        }
    }
}
