<?php

namespace App\Service;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Сервис заявок на закупку запчастей.
 *
 * Заявки хранятся в смарт-процессе «Заявки на закупку». Сервис умеет создавать
 * заявку (вручную сотрудником или автоматически по нулевому остатку),
 * одобрять её с пополнением склада и отклонять с указанием причины.
 * По каждому действию отправляется уведомление ответственному сотруднику.
 *
 * @package App\Service
 */
class PurchaseService
{
    /** @var string символьное имя смарт-процесса заявок */
    private const PURCHASE_TYPE_NAME = 'PurchaseRequest';

    /** @var int количество, закупаемое автоматически при нулевом остатке */
    public const AUTO_PURCHASE_QUANTITY = 10;

    /** @var string должность закупщика */
    private const POSITION_BUYER = 'Закупщик';

    /** @var string должность начальника отдела закупок */
    private const POSITION_HEAD = 'Начальник отдела закупок';

    /** @var int|null закэшированный тип заявок */
    private static ?int $typeId = null;

    /**
     * Возвращает ENTITY_TYPE_ID смарт-процесса заявок на закупку.
     *
     * @return int
     */
    public function getTypeId(): int
    {
        if (self::$typeId !== null)
        {
            return self::$typeId;
        }

        self::$typeId = 0;
        if (Loader::includeModule('crm'))
        {
            $row = \Bitrix\Crm\Model\Dynamic\TypeTable::getList([
                'filter' => ['=NAME' => self::PURCHASE_TYPE_NAME],
                'select' => ['ENTITY_TYPE_ID'],
                'limit' => 1,
            ])->fetch();
            self::$typeId = $row ? (int) $row['ENTITY_TYPE_ID'] : 0;
        }

        return self::$typeId;
    }

    /**
     * Создаёт заявку на закупку запчасти.
     *
     * @param int $productId идентификатор товара
     * @param string $productName название запчасти
     * @param int $quantity требуемое количество
     * @param int $initiatorId инициатор заявки
     * @param bool $isAuto создана ли заявка автоматически
     * @return int идентификатор заявки либо 0 при ошибке
     */
    public function createRequest(int $productId, string $productName, int $quantity, int $initiatorId, bool $isAuto = false): int
    {
        $typeId = $this->getTypeId();
        if ($typeId <= 0)
        {
            return 0;
        }

        $factory = Container::getInstance()->getFactory($typeId);
        if (!$factory)
        {
            return 0;
        }

        $item = $factory->createItem();
        $item->setTitle(Loc::getMessage('SERVICE_PURCHASE_TITLE', [
            '#PART#' => $productName,
            '#QUANTITY#' => $quantity,
        ]));
        $item->set('UF_CRM_PR_PART', $productName);
        $item->set('UF_CRM_PR_PRODUCT_ID', $productId);
        $item->set('UF_CRM_PR_QUANTITY', $quantity);
        $item->set('UF_CRM_PR_INITIATOR', $initiatorId);
        $item->set('UF_CRM_PR_AUTO', $isAuto ? 1 : 0);
        $item->set('ASSIGNED_BY_ID', $isAuto ? $this->getApprover() : $initiatorId);

        $operation = $factory->getAddOperation($item);
        $operation->disableAllChecks();
        $result = $operation->launch();

        if (!$result->isSuccess())
        {
            return 0;
        }

        return (int) $item->getId();
    }

    /**
     * Одобряет заявку: пополняет остаток и переводит заявку в «Выполнено».
     *
     * @param int $requestId идентификатор заявки
     * @param int $approverId сотрудник, одобривший заявку
     * @param bool $notifyInitiator отправлять ли инициатору уведомление об одобрении
     * @return bool успешность операции
     */
    public function approve(int $requestId, int $approverId = 0, bool $notifyInitiator = true): bool
    {
        $item = $this->getItem($requestId);
        if (!$item)
        {
            return false;
        }

        $productId = (int) $item->get('UF_CRM_PR_PRODUCT_ID');
        $quantity = (int) $item->get('UF_CRM_PR_QUANTITY');
        $partName = (string) $item->get('UF_CRM_PR_PART');
        $initiatorId = (int) $item->get('UF_CRM_PR_INITIATOR');

        (new StockService())->increaseQuantity($productId, $quantity);
        $this->moveToStage($item, 'SUCCESS');

        if ($notifyInitiator)
        {
            $this->notify($initiatorId, Loc::getMessage('SERVICE_PURCHASE_NOTIFY_APPROVED', [
                '#PART#' => $partName,
                '#QUANTITY#' => $quantity,
            ]));
        }

        return true;
    }

    /**
     * Отклоняет заявку с указанием причины, остаток не меняется.
     *
     * @param int $requestId идентификатор заявки
     * @param string $reason причина отказа
     * @param int $approverId сотрудник, отклонивший заявку
     * @return bool успешность операции
     */
    public function reject(int $requestId, string $reason, int $approverId = 0): bool
    {
        $item = $this->getItem($requestId);
        if (!$item)
        {
            return false;
        }

        $partName = (string) $item->get('UF_CRM_PR_PART');
        $initiatorId = (int) $item->get('UF_CRM_PR_INITIATOR');

        $item->set('UF_CRM_PR_REJECT_REASON', $reason);
        $this->moveToStage($item, 'FAIL');

        $this->notify($initiatorId, Loc::getMessage('SERVICE_PURCHASE_NOTIFY_REJECTED', [
            '#PART#' => $partName,
            '#REASON#' => $reason,
        ]));

        return true;
    }

    /**
     * Возвращает сотрудника, который согласует заявку.
     *
     * Приоритет у закупщиков; если ни одного активного закупщика нет,
     * заявка уходит начальнику отдела закупок.
     *
     * @return int идентификатор сотрудника
     */
    public function getApprover(): int
    {
        $buyers = $this->getUsersByPosition(self::POSITION_BUYER);
        if (!empty($buyers))
        {
            return (int) $buyers[0];
        }

        $heads = $this->getUsersByPosition(self::POSITION_HEAD);

        return !empty($heads) ? (int) $heads[0] : 1;
    }

    /**
     * Возвращает активных сотрудников с указанной должностью.
     *
     * @param string $position должность
     * @return int[] идентификаторы сотрудников
     */
    public function getUsersByPosition(string $position): array
    {
        $ids = [];
        $res = \Bitrix\Main\UserTable::getList([
            'filter' => ['=WORK_POSITION' => $position, '=ACTIVE' => 'Y'],
            'select' => ['ID'],
        ]);
        while ($row = $res->fetch())
        {
            $ids[] = (int) $row['ID'];
        }

        return $ids;
    }

    /**
     * Отправляет уведомление сотруднику.
     *
     * @param int $userId получатель
     * @param string $message текст уведомления
     * @return void
     */
    public function notify(int $userId, string $message): void
    {
        if ($userId <= 0 || !Loader::includeModule('im'))
        {
            return;
        }

        \CIMNotify::Add([
            'TO_USER_ID' => $userId,
            'FROM_USER_ID' => 0,
            'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
            'NOTIFY_MODULE' => 'main',
            'NOTIFY_MESSAGE' => $message,
        ]);
    }

    /**
     * Возвращает элемент заявки.
     *
     * @param int $requestId идентификатор заявки
     * @return \Bitrix\Crm\Item|null
     */
    private function getItem(int $requestId): ?\Bitrix\Crm\Item
    {
        $typeId = $this->getTypeId();
        if ($typeId <= 0 || $requestId <= 0)
        {
            return null;
        }

        $factory = Container::getInstance()->getFactory($typeId);

        return $factory ? $factory->getItem($requestId) : null;
    }

    /**
     * Переводит заявку на указанную стадию.
     *
     * @param \Bitrix\Crm\Item $item заявка
     * @param string $stageCode код стадии (SUCCESS, FAIL и т.д.)
     * @return void
     */
    private function moveToStage(\Bitrix\Crm\Item $item, string $stageCode): void
    {
        $typeId = $this->getTypeId();
        $factory = Container::getInstance()->getFactory($typeId);
        if (!$factory)
        {
            return;
        }

        $categoryId = (int) $item->getCategoryId();
        $stageId = 'DT' . $typeId . '_' . $categoryId . ':' . $stageCode;
        $item->set('STAGE_ID', $stageId);

        $operation = $factory->getUpdateOperation($item);
        $operation->disableAllChecks();
        $operation->launch();
    }
}
