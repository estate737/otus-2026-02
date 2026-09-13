<?php

namespace App\Service;

use Bitrix\Catalog\GroupTable;
use Bitrix\Catalog\PriceTable;
use Bitrix\Crm\Item;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserTable;

Loc::loadMessages(__FILE__);

/**
 * Сервис заявок на закупку запчастей.
 *
 * Заявка хранится в смарт-процессе «Заявки на закупку», её состав - в товарных
 * позициях каталога. Решение закупщика фиксируется стадией: «Выполнено» пополняет
 * склад, «Отклонено» требует причину. Решение обрабатывается по событию сохранения
 * элемента, поэтому одинаково срабатывает из карточки, канбана, REST и кода.
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

    /** @var string код стадии ожидания решения закупщика */
    private const STAGE_APPROVAL = 'PREPARATION';

    /** @var string код стадии одобренной заявки */
    private const STAGE_SUCCESS = 'SUCCESS';

    /** @var string код стадии отклонённой заявки */
    private const STAGE_FAIL = 'FAIL';

    /** @var string поле инициатора заявки */
    public const FIELD_INITIATOR = 'UF_CRM_PR_INITIATOR';

    /** @var string поле причины отказа */
    public const FIELD_REJECT_REASON = 'UF_CRM_PR_REJECT_REASON';

    /** @var string признак заявки, созданной автоматически */
    public const FIELD_AUTO = 'UF_CRM_PR_AUTO';

    /** @var string признак того, что решение по заявке уже выполнено */
    public const FIELD_PROCESSED = 'UF_CRM_PR_PROCESSED';

    /** @var int|null закэшированный тип заявок */
    private static ?int $typeId = null;

    /**
     * Возвращает ENTITY_TYPE_ID смарт-процесса заявок на закупку.
     *
     * @return int
     */
    public function getTypeId(): int
    {
        if (self::$typeId !== null) {
            return self::$typeId;
        }

        self::$typeId = 0;
        if (Loader::includeModule('crm')) {
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
     * Создаёт заявку на закупку одной запчасти.
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
        return $this->createRequestForParts(
            [['ID' => $productId, 'NAME' => $productName, 'QUANTITY' => $quantity]],
            $initiatorId,
            $isAuto
        );
    }

    /**
     * Создаёт заявку на закупку со списком запчастей.
     *
     * Состав записывается в товарные позиции в той же операции, что и сама заявка.
     *
     * @param array<int, array{ID: int, NAME: string, QUANTITY: int, PRICE?: float}> $parts запчасти
     * @param int $initiatorId инициатор заявки
     * @param bool $isAuto создана ли заявка автоматически
     * @return int идентификатор заявки либо 0 при ошибке
     */
    public function createRequestForParts(array $parts, int $initiatorId, bool $isAuto = false): int
    {
        $factory = $this->getFactory();
        $parts = array_values(array_filter($parts, static fn (array $part) => (int) ($part['ID'] ?? 0) > 0));
        if ($factory === null || empty($parts)) {
            return 0;
        }

        $rows = [];
        $titleParts = [];
        foreach ($parts as $part) {
            $productId = (int) $part['ID'];
            $quantity = max(1, (int) ($part['QUANTITY'] ?? 1));
            $rows[] = [
                'PRODUCT_ID' => $productId,
                'PRODUCT_NAME' => (string) $part['NAME'],
                'QUANTITY' => $quantity,
                'PRICE' => isset($part['PRICE']) ? (float) $part['PRICE'] : $this->getPartPrice($productId),
            ];
            $titleParts[] = ['PRODUCT_NAME' => (string) $part['NAME'], 'QUANTITY' => $quantity];
        }

        $item = $factory->createItem();
        $item->setTitle($this->buildTitle($titleParts));
        $item->setProductRowsFromArrays($rows);
        $item->set(self::FIELD_INITIATOR, $initiatorId);
        $item->set(self::FIELD_AUTO, $isAuto);
        $item->set(Item::FIELD_NAME_ASSIGNED, $isAuto ? $this->getApprover() : $initiatorId);

        $operation = $factory->getAddOperation($item);
        $operation->disableAllChecks();

        return $operation->launch()->isSuccess() ? (int) $item->getId() : 0;
    }

    /**
     * Готовит созданную вручную заявку к согласованию.
     *
     * Назначает согласующего, фиксирует инициатора, переводит заявку
     * на стадию согласования и уведомляет закупщика.
     *
     * @param Item $item созданная заявка
     * @return void
     */
    public function prepareNewRequest(Item $item): void
    {
        $request = $this->getItem((int) $item->getId());
        if ($request === null || $request->get(self::FIELD_AUTO)) {
            return;
        }

        $initiatorId = (int) $request->get(self::FIELD_INITIATOR);
        if ($initiatorId <= 0) {
            $initiatorId = (int) $request->get(Item::FIELD_NAME_CREATED_BY);
        }

        $approverId = $this->getApprover();
        $parts = $this->getRequestParts((int) $request->getId());

        $request->set(self::FIELD_INITIATOR, $initiatorId);
        $request->set(Item::FIELD_NAME_ASSIGNED, $approverId);
        $request->setStageId($this->getStageId($request, self::STAGE_APPROVAL));
        if (!empty($parts)) {
            $request->setTitle($this->buildTitle($parts));
        }

        if (!$this->save($request)) {
            return;
        }

        $this->notify($approverId, Loc::getMessage('SERVICE_PURCHASE_NOTIFY_NEW', [
            '#ID#' => $request->getId(),
            '#LINK#' => $this->getRequestUrl((int) $request->getId()),
            '#PARTS#' => $this->formatParts($parts),
        ]), $this->getNotifyTag((int) $request->getId()));
    }

    /**
     * Выполняет решение по заявке после смены стадии.
     *
     * Финальная стадия обрабатывается один раз: признак обработки исключает
     * повторное пополнение склада при последующих сохранениях.
     *
     * @param Item $item сохранённая заявка
     * @return void
     */
    public function processDecision(Item $item): void
    {
        $request = $this->getItem((int) $item->getId());
        if ($request === null || $request->get(self::FIELD_PROCESSED)) {
            return;
        }

        switch ($this->getStageSemantics($request)) {
            case PhaseSemantics::SUCCESS:
                $this->applyApproval($request);
                break;
            case PhaseSemantics::FAILURE:
                $this->applyRejection($request);
                break;
            default:
                $this->refreshTitle($request);
        }
    }

    /**
     * Одобряет заявку: переводит её в «Выполнено».
     *
     * Склад пополняет обработчик решения, как и при одобрении из карточки.
     *
     * @param int $requestId идентификатор заявки
     * @return bool успешность операции
     */
    public function approve(int $requestId): bool
    {
        $request = $this->getItem($requestId);
        if ($request === null) {
            return false;
        }

        $request->setStageId($this->getStageId($request, self::STAGE_SUCCESS));

        return $this->save($request);
    }

    /**
     * Отклоняет заявку с указанием причины.
     *
     * @param int $requestId идентификатор заявки
     * @param string $reason причина отказа
     * @return bool успешность операции
     */
    public function reject(int $requestId, string $reason): bool
    {
        $request = $this->getItem($requestId);
        if ($request === null) {
            return false;
        }

        $request->set(self::FIELD_REJECT_REASON, $reason);
        $request->setStageId($this->getStageId($request, self::STAGE_FAIL));

        return $this->save($request);
    }

    /**
     * Возвращает состав заявки из товарных позиций.
     *
     * @param int $requestId идентификатор заявки
     * @return array<int, array{PRODUCT_ID: int, PRODUCT_NAME: string, QUANTITY: int}>
     */
    public function getRequestParts(int $requestId): array
    {
        $rows = \CCrmProductRow::LoadRows(\CCrmOwnerTypeAbbr::ResolveByTypeID($this->getTypeId()), $requestId);
        $parts = [];
        foreach ((array) $rows as $row) {
            $parts[] = [
                'PRODUCT_ID' => (int) $row['PRODUCT_ID'],
                'PRODUCT_NAME' => (string) $row['PRODUCT_NAME'],
                'QUANTITY' => (int) $row['QUANTITY'],
            ];
        }

        return $parts;
    }

    /**
     * Возвращает сотрудника, который согласует заявку.
     *
     * Приоритет у закупщиков; если ни одного доступного закупщика нет,
     * заявка уходит начальнику отдела закупок.
     *
     * @return int идентификатор сотрудника
     */
    public function getApprover(): int
    {
        foreach ($this->getUsersByPosition(self::POSITION_BUYER) as $buyerId) {
            if ($this->isAvailable($buyerId)) {
                return $buyerId;
            }
        }

        $heads = $this->getUsersByPosition(self::POSITION_HEAD);

        return !empty($heads) ? $heads[0] : 1;
    }

    /**
     * Проверяет, доступен ли сотрудник (не в отпуске и не в отсутствии).
     *
     * @param int $userId идентификатор сотрудника
     * @return bool
     */
    public function isAvailable(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        if (!Loader::includeModule('intranet') || !method_exists('CIntranetUtils', 'GetAbsenceData')) {
            return true;
        }

        $absence = \CIntranetUtils::GetAbsenceData(
            ['USERS' => [$userId], 'DATE_START' => date('d.m.Y'), 'DATE_FINISH' => date('d.m.Y')],
            BX_INTRANET_ABSENCE_ALL
        );

        return empty($absence[$userId]);
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
        $res = UserTable::getList([
            'filter' => ['=WORK_POSITION' => $position, '=ACTIVE' => 'Y'],
            'select' => ['ID'],
            'order' => ['ID' => 'ASC'],
        ]);
        while ($row = $res->fetch()) {
            $ids[] = (int) $row['ID'];
        }

        return $ids;
    }

    /**
     * Возвращает тег уведомлений по заявке.
     *
     * По тегу уведомления одной заявки группируются и при необходимости удаляются.
     *
     * @param int $requestId идентификатор заявки
     * @return string
     */
    public function getNotifyTag(int $requestId): string
    {
        return 'SERVICE_PURCHASE|' . $requestId;
    }

    /**
     * Отправляет уведомление сотруднику.
     *
     * @param int $userId получатель
     * @param string $message текст уведомления
     * @param string $tag тег уведомления
     * @return void
     */
    public function notify(int $userId, string $message, string $tag = ''): void
    {
        if ($userId <= 0 || !Loader::includeModule('im')) {
            return;
        }

        \CIMNotify::Add([
            'TO_USER_ID' => $userId,
            'FROM_USER_ID' => 0,
            'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
            'NOTIFY_MODULE' => 'main',
            'NOTIFY_MESSAGE' => $message,
            'NOTIFY_TAG' => $tag,
        ]);
    }

    /**
     * Пополняет склад по одобренной заявке и уведомляет инициатора.
     *
     * @param Item $request заявка в стадии «Выполнено»
     * @return void
     */
    private function applyApproval(Item $request): void
    {
        $request->set(self::FIELD_PROCESSED, true);
        if (!$this->save($request)) {
            return;
        }

        $parts = $this->getRequestParts((int) $request->getId());
        $stock = new StockService();
        foreach ($parts as $part) {
            $stock->increaseQuantity($part['PRODUCT_ID'], $part['QUANTITY']);
        }

        if ($request->get(self::FIELD_AUTO)) {
            return;
        }

        $this->notify((int) $request->get(self::FIELD_INITIATOR), Loc::getMessage('SERVICE_PURCHASE_NOTIFY_APPROVED', [
            '#ID#' => $request->getId(),
            '#LINK#' => $this->getRequestUrl((int) $request->getId()),
            '#PARTS#' => $this->formatParts($parts),
        ]), $this->getNotifyTag((int) $request->getId()));
    }

    /**
     * Фиксирует отказ и отправляет инициатору причину.
     *
     * @param Item $request заявка в стадии «Отклонено»
     * @return void
     */
    private function applyRejection(Item $request): void
    {
        $request->set(self::FIELD_PROCESSED, true);
        if (!$this->save($request)) {
            return;
        }

        $reason = trim((string) $request->get(self::FIELD_REJECT_REASON));

        $this->notify((int) $request->get(self::FIELD_INITIATOR), Loc::getMessage('SERVICE_PURCHASE_NOTIFY_REJECTED', [
            '#ID#' => $request->getId(),
            '#LINK#' => $this->getRequestUrl((int) $request->getId()),
            '#PARTS#' => $this->formatParts($this->getRequestParts((int) $request->getId())),
            '#REASON#' => $reason !== '' ? $reason : Loc::getMessage('SERVICE_PURCHASE_REASON_EMPTY'),
        ]), $this->getNotifyTag((int) $request->getId()));
    }

    /**
     * Обновляет название заявки по её текущему составу.
     *
     * @param Item $request заявка в работе
     * @return void
     */
    private function refreshTitle(Item $request): void
    {
        $parts = $this->getRequestParts((int) $request->getId());
        if (empty($parts)) {
            return;
        }

        $title = $this->buildTitle($parts);
        if ((string) $request->getTitle() !== $title) {
            $request->setTitle($title);
            $this->save($request);
        }
    }

    /**
     * Формирует название заявки по составу.
     *
     * @param array<int, array{PRODUCT_NAME: string, QUANTITY: int}> $parts состав заявки
     * @return string
     */
    private function buildTitle(array $parts): string
    {
        $total = array_sum(array_map(static fn (array $part) => (int) $part['QUANTITY'], $parts));

        if (count($parts) === 1) {
            return Loc::getMessage('SERVICE_PURCHASE_TITLE', [
                '#PART#' => $parts[0]['PRODUCT_NAME'],
                '#QUANTITY#' => $total,
            ]);
        }

        return Loc::getMessage('SERVICE_PURCHASE_TITLE_MANY', [
            '#COUNT#' => count($parts),
            '#WORD#' => $this->getPositionsWord(count($parts)),
            '#QUANTITY#' => $total,
        ]);
    }

    /**
     * Перечисляет позиции заявки для уведомления.
     *
     * @param array<int, array{PRODUCT_NAME: string, QUANTITY: int}> $parts состав заявки
     * @return string
     */
    private function formatParts(array $parts): string
    {
        return implode(', ', array_map(static fn (array $part) => Loc::getMessage('SERVICE_PURCHASE_PART_LINE', [
            '#PART#' => $part['PRODUCT_NAME'],
            '#QUANTITY#' => $part['QUANTITY'],
        ]), $parts));
    }

    /**
     * Подбирает форму слова «позиция» для числа.
     *
     * @param int $count количество позиций
     * @return string
     */
    private function getPositionsWord(int $count): string
    {
        $mod10 = $count % 10;
        $mod100 = $count % 100;

        if ($mod10 === 1 && $mod100 !== 11) {
            return Loc::getMessage('SERVICE_PURCHASE_WORD_ONE');
        }

        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
            return Loc::getMessage('SERVICE_PURCHASE_WORD_FEW');
        }

        return Loc::getMessage('SERVICE_PURCHASE_WORD_MANY');
    }

    /**
     * Возвращает базовую цену запчасти из каталога.
     *
     * @param int $productId идентификатор товара
     * @return float
     */
    private function getPartPrice(int $productId): float
    {
        if ($productId <= 0 || !Loader::includeModule('catalog')) {
            return 0.0;
        }

        $row = PriceTable::getList([
            'filter' => ['=PRODUCT_ID' => $productId, '=CATALOG_GROUP_ID' => GroupTable::getBasePriceTypeId()],
            'select' => ['PRICE'],
            'limit' => 1,
        ])->fetch();

        return $row ? (float) $row['PRICE'] : 0.0;
    }

    /**
     * Возвращает адрес карточки заявки.
     *
     * @param int $requestId идентификатор заявки
     * @return string
     */
    private function getRequestUrl(int $requestId): string
    {
        $url = Container::getInstance()->getRouter()->getItemDetailUrl($this->getTypeId(), $requestId);

        return $url !== null ? (string) $url : '';
    }

    /**
     * Возвращает семантику текущей стадии заявки.
     *
     * @param Item $request заявка
     * @return string одна из констант PhaseSemantics
     */
    private function getStageSemantics(Item $request): string
    {
        $stage = $this->getFactory()?->getStage((string) $request->getStageId());
        $semantics = $stage ? (string) $stage->getSemantics() : '';

        return $semantics !== '' ? $semantics : PhaseSemantics::PROCESS;
    }

    /**
     * Собирает идентификатор стадии в воронке заявки.
     *
     * @param Item $request заявка
     * @param string $code код стадии
     * @return string
     */
    private function getStageId(Item $request, string $code): string
    {
        return 'DT' . $this->getTypeId() . '_' . (int) $request->getCategoryId() . ':' . $code;
    }

    /**
     * Возвращает заявку по идентификатору.
     *
     * @param int $requestId идентификатор заявки
     * @return Item|null
     */
    private function getItem(int $requestId): ?Item
    {
        $factory = $this->getFactory();

        return ($factory !== null && $requestId > 0) ? $factory->getItem($requestId) : null;
    }

    /**
     * Возвращает фабрику смарт-процесса заявок.
     *
     * @return Factory|null
     */
    private function getFactory(): ?Factory
    {
        $typeId = $this->getTypeId();

        return $typeId > 0 ? Container::getInstance()->getFactory($typeId) : null;
    }

    /**
     * Сохраняет заявку.
     *
     * @param Item $request заявка
     * @return bool успешность сохранения
     */
    private function save(Item $request): bool
    {
        $factory = $this->getFactory();
        if ($factory === null) {
            return false;
        }

        $operation = $factory->getUpdateOperation($request);
        $operation->disableAllChecks();

        return $operation->launch()->isSuccess();
    }
}
