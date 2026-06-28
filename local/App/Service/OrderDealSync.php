<?php

namespace App\Service;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Двусторонняя синхронизация элементов инфоблока «Заявки» и сделок CRM.
 *
 * Заявка хранит ссылку на сделку (свойство DEAL_ID), сумму (SUM) и
 * ответственного (RESPONSIBLE). Сервис переносит эти значения в связанную
 * сделку и обратно, из сделки в связанные заявки. Используется обработчиками
 * событий инфоблока и CRM.
 *
 * @package App\Service
 */
class OrderDealSync
{
    /** @var string символьный код инфоблока «Заявки» */
    private const IBLOCK_CODE = 'requests';

    /** @var string тип инфоблока «Заявки» */
    private const IBLOCK_TYPE = 'otus_orders';

    /** @var string код свойства «Сделка» (ID связанной сделки) */
    public const PROP_DEAL = 'DEAL_ID';

    /** @var string код свойства «Сумма» */
    public const PROP_SUM = 'SUM';

    /** @var string код свойства «Ответственный» (ID пользователя) */
    public const PROP_RESPONSIBLE = 'RESPONSIBLE';

    /** @var int|null закэшированный ID инфоблока «Заявки» */
    private static ?int $iblockId = null;

    /**
     * Возвращает ID инфоблока «Заявки», разрешая его по символьному коду.
     *
     * @return int ID инфоблока либо 0, если инфоблок не найден
     */
    public function getIblockId(): int
    {
        if (self::$iblockId === null)
        {
            $row = \CIBlock::GetList([], [
                'TYPE' => self::IBLOCK_TYPE,
                'CODE' => self::IBLOCK_CODE,
                'CHECK_PERMISSIONS' => 'N',
            ])->Fetch();
            self::$iblockId = (int) ($row['ID'] ?? 0);
        }

        return self::$iblockId;
    }

    /**
     * Читает значимые поля заявки по её ID.
     *
     * @param int $orderId ID элемента инфоблока
     * @return array{DEAL_ID: int, SUM: float, RESPONSIBLE: int}|null
     */
    public function readOrder(int $orderId): ?array
    {
        $row = \CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $this->getIblockId(), 'ID' => $orderId, 'CHECK_PERMISSIONS' => 'N'],
            false,
            false,
            [
                'ID',
                'PROPERTY_' . self::PROP_DEAL,
                'PROPERTY_' . self::PROP_SUM,
                'PROPERTY_' . self::PROP_RESPONSIBLE,
            ]
        )->Fetch();

        if (!$row)
        {
            return null;
        }

        return [
            'DEAL_ID' => (int) ($row['PROPERTY_' . self::PROP_DEAL . '_VALUE'] ?? 0),
            'SUM' => (float) ($row['PROPERTY_' . self::PROP_SUM . '_VALUE'] ?? 0),
            'RESPONSIBLE' => (int) ($row['PROPERTY_' . self::PROP_RESPONSIBLE . '_VALUE'] ?? 0),
        ];
    }

    /**
     * Читает сумму и ответственного сделки по её ID.
     *
     * @param int $dealId ID сделки
     * @return array{OPPORTUNITY: float, ASSIGNED_BY_ID: int}|null
     */
    public function readDeal(int $dealId): ?array
    {
        $row = \CCrmDeal::GetListEx(
            [],
            ['=ID' => $dealId, 'CHECK_PERMISSIONS' => 'N'],
            false,
            false,
            ['ID', 'OPPORTUNITY', 'ASSIGNED_BY_ID']
        )->Fetch();

        if (!$row)
        {
            return null;
        }

        return [
            'OPPORTUNITY' => (float) $row['OPPORTUNITY'],
            'ASSIGNED_BY_ID' => (int) $row['ASSIGNED_BY_ID'],
        ];
    }

    /**
     * Возвращает ID заявок, связанных с указанной сделкой.
     *
     * @param int $dealId ID сделки
     * @return int[]
     */
    public function findOrdersByDeal(int $dealId): array
    {
        $ids = [];
        $res = \CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $this->getIblockId(), 'PROPERTY_' . self::PROP_DEAL => $dealId, 'CHECK_PERMISSIONS' => 'N'],
            false,
            false,
            ['ID']
        );
        while ($row = $res->Fetch())
        {
            $ids[] = (int) $row['ID'];
        }

        return $ids;
    }

    /**
     * Переносит сумму и ответственного из заявки в связанную сделку.
     *
     * @param int $orderId ID заявки
     * @return bool была ли обновлена сделка
     */
    public function syncOrderToDeal(int $orderId): bool
    {
        $order = $this->readOrder($orderId);
        if ($order === null || $order['DEAL_ID'] <= 0)
        {
            return false;
        }

        $fields = ['OPPORTUNITY' => $order['SUM']];
        if ($order['RESPONSIBLE'] > 0)
        {
            $fields['ASSIGNED_BY_ID'] = $order['RESPONSIBLE'];
        }

        $deal = new \CCrmDeal(false);
        $updated = (bool) $deal->Update($order['DEAL_ID'], $fields, true, true, ['DISABLE_USER_FIELD_CHECK' => true]);

        if ($updated)
        {
            $this->log($orderId, Loc::getMessage('OTUS_ODS_LOG_ORDER_TO_DEAL', [
                '#ORDER#' => $orderId,
                '#DEAL#' => $order['DEAL_ID'],
            ]));
        }

        return $updated;
    }

    /**
     * Переносит сумму и ответственного из сделки во все связанные заявки.
     *
     * @param int $dealId ID сделки
     * @return int число обновлённых заявок
     */
    public function syncDealToOrders(int $dealId): int
    {
        $deal = $this->readDeal($dealId);
        if ($deal === null)
        {
            return 0;
        }

        $count = 0;
        foreach ($this->findOrdersByDeal($dealId) as $orderId)
        {
            \CIBlockElement::SetPropertyValuesEx($orderId, $this->getIblockId(), [
                self::PROP_SUM => $deal['OPPORTUNITY'],
                self::PROP_RESPONSIBLE => $deal['ASSIGNED_BY_ID'],
            ]);
            $count++;
        }

        if ($count > 0)
        {
            $this->log($dealId, Loc::getMessage('OTUS_ODS_LOG_DEAL_TO_ORDERS', [
                '#DEAL#' => $dealId,
                '#COUNT#' => $count,
            ]));
        }

        return $count;
    }

    /**
     * Снимает у заявок ссылку на удаляемую сделку.
     *
     * @param int $dealId ID сделки
     * @return int число отвязанных заявок
     */
    public function unlinkOrdersFromDeal(int $dealId): int
    {
        $count = 0;
        foreach ($this->findOrdersByDeal($dealId) as $orderId)
        {
            \CIBlockElement::SetPropertyValuesEx($orderId, $this->getIblockId(), [self::PROP_DEAL => false]);
            $count++;
        }

        if ($count > 0)
        {
            $this->log($dealId, Loc::getMessage('OTUS_ODS_LOG_UNLINK', [
                '#DEAL#' => $dealId,
                '#COUNT#' => $count,
            ]));
        }

        return $count;
    }

    /**
     * Пишет запись о синхронизации в журнал событий Битрикс.
     *
     * @param int $itemId ID сущности-источника
     * @param string $message текст записи
     * @return void
     */
    private function log(int $itemId, string $message): void
    {
        \CEventLog::Log('INFO', 'OTUS_ORDER_DEAL_SYNC', 'main', $itemId, $message);
    }
}
