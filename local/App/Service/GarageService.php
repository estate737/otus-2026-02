<?php

namespace App\Service;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Сервис «Гараж клиента»: автомобили контакта и история их обслуживания.
 *
 * Автомобили хранятся в смарт-процессе (кастомная сущность CRM), поэтому
 * доступны и через REST для внешних систем. История ремонтов собирается по
 * сделкам воронки сервисного обслуживания, связанным с автомобилем.
 *
 * @package App\Service
 */
class GarageService
{
    /** @var string символьное имя смарт-процесса автомобилей */
    private const CAR_TYPE_NAME = 'Car';

    /** @var string поле сделки со ссылкой на автомобиль */
    public const DEAL_CAR_FIELD = 'UF_CRM_DEAL_CAR';

    /** @var string поле сделки со списком запчастей */
    public const DEAL_PARTS_FIELD = 'UF_CRM_DEAL_PARTS';

    /** @var int|null закэшированный идентификатор типа автомобилей */
    private static ?int $carTypeId = null;

    /**
     * Возвращает ENTITY_TYPE_ID смарт-процесса автомобилей.
     *
     * @return int идентификатор типа либо 0, если смарт-процесс не найден
     */
    public function getCarTypeId(): int
    {
        if (self::$carTypeId !== null)
        {
            return self::$carTypeId;
        }

        self::$carTypeId = 0;
        if (!Loader::includeModule('crm'))
        {
            return 0;
        }

        $row = \Bitrix\Crm\Model\Dynamic\TypeTable::getList([
            'filter' => ['=NAME' => self::CAR_TYPE_NAME],
            'select' => ['ENTITY_TYPE_ID'],
            'limit' => 1,
        ])->fetch();

        self::$carTypeId = $row ? (int) $row['ENTITY_TYPE_ID'] : 0;

        return self::$carTypeId;
    }

    /**
     * Возвращает автомобили клиента.
     *
     * @param int $contactId идентификатор контакта
     * @return array<int, array<string, mixed>> список автомобилей
     */
    public function getCarsByContact(int $contactId): array
    {
        $typeId = $this->getCarTypeId();
        if ($typeId <= 0 || $contactId <= 0)
        {
            return [];
        }

        $factory = Container::getInstance()->getFactory($typeId);
        if (!$factory)
        {
            return [];
        }

        $items = $factory->getItems([
            'filter' => ['=CONTACT_ID' => $contactId],
            'order' => ['ID' => 'ASC'],
        ]);

        $cars = [];
        foreach ($items as $item)
        {
            $cars[] = [
                'ID' => $item->getId(),
                'TITLE' => (string) $item->getTitle(),
                'BRAND' => (string) $item->get('UF_CRM_CAR_BRAND'),
                'MODEL' => (string) $item->get('UF_CRM_CAR_MODEL'),
                'NUMBER' => (string) $item->get('UF_CRM_CAR_NUMBER'),
                'YEAR' => (int) $item->get('UF_CRM_CAR_YEAR'),
                'COLOR' => (string) $item->get('UF_CRM_CAR_COLOR'),
                'MILEAGE' => (int) $item->get('UF_CRM_CAR_MILEAGE'),
                'VIN' => (string) $item->get('UF_CRM_CAR_VIN'),
            ];
        }

        return $cars;
    }

    /**
     * Возвращает автомобиль по идентификатору.
     *
     * @param int $carId идентификатор автомобиля
     * @return array<string, mixed>|null данные автомобиля
     */
    public function getCar(int $carId): ?array
    {
        $typeId = $this->getCarTypeId();
        if ($typeId <= 0 || $carId <= 0)
        {
            return null;
        }

        $factory = Container::getInstance()->getFactory($typeId);
        $item = $factory ? $factory->getItem($carId) : null;
        if (!$item)
        {
            return null;
        }

        return [
            'ID' => $item->getId(),
            'TITLE' => (string) $item->getTitle(),
            'NUMBER' => (string) $item->get('UF_CRM_CAR_NUMBER'),
            'CONTACT_ID' => (int) $item->get('CONTACT_ID'),
        ];
    }

    /**
     * Возвращает историю обслуживания автомобиля (заказ-наряды).
     *
     * @param int $carId идентификатор автомобиля
     * @return array<int, array<string, mixed>> список сделок
     */
    public function getServiceHistory(int $carId): array
    {
        if (!Loader::includeModule('crm') || $carId <= 0)
        {
            return [];
        }

        $result = \CCrmDeal::GetListEx(
            ['DATE_CREATE' => 'DESC'],
            ['=' . self::DEAL_CAR_FIELD => $carId, 'CHECK_PERMISSIONS' => 'N'],
            false,
            false,
            ['ID', 'TITLE', 'STAGE_ID', 'DATE_CREATE', 'OPPORTUNITY', 'CURRENCY_ID', 'ASSIGNED_BY_ID', self::DEAL_PARTS_FIELD]
        );

        $deals = [];
        while ($row = $result->Fetch())
        {
            // запчасти берутся из товарных позиций сделки (каталог товаров)
            $parts = (new OrderService())->getParts((int) $row['ID']);

            $deals[] = [
                'ID' => (int) $row['ID'],
                'TITLE' => (string) $row['TITLE'],
                'STAGE_ID' => (string) $row['STAGE_ID'],
                'STAGE_NAME' => $this->getStageName((string) $row['STAGE_ID']),
                'DATE_CREATE' => (string) $row['DATE_CREATE'],
                'OPPORTUNITY' => (float) $row['OPPORTUNITY'],
                'CURRENCY_ID' => (string) $row['CURRENCY_ID'],
                'ASSIGNED_BY_ID' => (int) $row['ASSIGNED_BY_ID'],
                'ASSIGNED_BY_NAME' => $this->getUserName((int) $row['ASSIGNED_BY_ID']),
                'PARTS' => array_values(array_filter($parts)),
            ];
        }

        return $deals;
    }

    /**
     * Проверяет наличие незакрытых заказ-нарядов по автомобилю.
     *
     * @param int $carId идентификатор автомобиля
     * @param int $exceptDealId сделка, которую нужно исключить из проверки
     * @return array<int, array<string, mixed>> незакрытые сделки
     */
    public function getOpenDeals(int $carId, int $exceptDealId = 0): array
    {
        $open = [];
        foreach ($this->getServiceHistory($carId) as $deal)
        {
            if ($exceptDealId > 0 && $deal['ID'] === $exceptDealId)
            {
                continue;
            }

            $semantics = \CCrmDeal::GetSemanticID($deal['STAGE_ID'], \CCrmDeal::GetCategoryID($deal['ID']));
            if ($semantics === \Bitrix\Crm\PhaseSemantics::PROCESS)
            {
                $open[] = $deal;
            }
        }

        return $open;
    }

    /**
     * Возвращает название стадии сделки.
     *
     * @param string $stageId идентификатор стадии
     * @return string
     */
    private function getStageName(string $stageId): string
    {
        static $names = null;
        if ($names === null)
        {
            $names = [];
            $res = \CCrmStatus::GetList([], []);
            while ($row = $res->Fetch())
            {
                $names[$row['STATUS_ID']] = $row['NAME'];
            }
        }

        return $names[$stageId] ?? $stageId;
    }

    /**
     * Возвращает имя сотрудника.
     *
     * @param int $userId идентификатор пользователя
     * @return string
     */
    private function getUserName(int $userId): string
    {
        static $cache = [];
        if ($userId <= 0)
        {
            return '';
        }

        if (!isset($cache[$userId]))
        {
            $user = \Bitrix\Main\UserTable::getList([
                'filter' => ['=ID' => $userId],
                'select' => ['ID', 'NAME', 'LAST_NAME', 'LOGIN'],
                'limit' => 1,
            ])->fetch();

            $cache[$userId] = $user
                ? trim($user['NAME'] . ' ' . $user['LAST_NAME']) ?: $user['LOGIN']
                : '';
        }

        return $cache[$userId];
    }
}
