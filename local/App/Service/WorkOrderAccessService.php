<?php

namespace App\Service;

use Bitrix\Crm\Binding\DealContactTable;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

/**
 * Доступ механика к клиенту и автомобилю его заказ-наряда.
 *
 * Роль механика открывает только записи, где он ответственный, а клиенты и
 * автомобили закреплены за менеджерами. Ответственный за заказ-наряд
 * добавляется в наблюдатели клиента и автомобиля: права CRM уровня «Свои»
 * открывают запись и наблюдателю.
 *
 * @package App\Service
 */
class WorkOrderAccessService
{
    /**
     * Открывает ответственному за заказ-наряд клиента и автомобиль.
     *
     * Клиенты - контакты сделки и владелец автомобиля. Доступ передаётся только
     * к записям, которые может открыть сотрудник, сохранивший заказ-наряд:
     * подставив в свой заказ-наряд чужой автомобиль, механик доступа к нему
     * не получит.
     *
     * @param int $dealId идентификатор сделки
     * @param int $grantorId сотрудник, сохранивший сделку; 0 - система
     * @return int число записей, в которые добавлен наблюдатель
     */
    public function grantForDeal(int $dealId, int $grantorId = 0): int
    {
        if ($dealId <= 0 || !Loader::includeModule('crm')) {
            return 0;
        }

        $deal = \CCrmDeal::GetListEx(
            [],
            ['=ID' => $dealId, 'CHECK_PERMISSIONS' => 'N'],
            false,
            false,
            ['ID', 'CATEGORY_ID', 'ASSIGNED_BY_ID', GarageService::DEAL_CAR_FIELD]
        )->Fetch();

        $categoryId = (int) Option::get('main', '~service_center_category', 1);
        $userId = $deal ? (int) $deal['ASSIGNED_BY_ID'] : 0;
        if ($userId <= 0 || (int) $deal['CATEGORY_ID'] !== $categoryId) {
            return 0;
        }

        $targets = [];
        $contactIds = DealContactTable::getDealContactIDs($dealId);

        $garage = new GarageService();
        $carId = (int) $deal[GarageService::DEAL_CAR_FIELD];
        $car = $garage->getCar($carId);
        if ($car !== null) {
            $targets[] = [$garage->getCarTypeId(), $carId];
            $contactIds[] = $car['CONTACT_ID'];
        }

        foreach (array_filter(array_unique(array_map('intval', $contactIds))) as $contactId) {
            $targets[] = [\CCrmOwnerType::Contact, $contactId];
        }

        $grantor = $grantorId > 0 ? Container::getInstance()->getUserPermissions($grantorId)->item() : null;
        $granted = 0;
        foreach ($targets as [$entityTypeId, $entityId]) {
            if ($grantor === null || $grantor->canRead($entityTypeId, $entityId)) {
                $granted += (int) $this->addObserver($entityTypeId, $entityId, $userId);
            }
        }

        return $granted;
    }

    /**
     * Добавляет сотрудника в наблюдатели записи CRM, если роль не даёт ему доступ.
     *
     * @param int $entityTypeId тип сущности
     * @param int $entityId идентификатор записи
     * @param int $userId сотрудник
     * @return bool true, если наблюдатель добавлен
     */
    public function addObserver(int $entityTypeId, int $entityId, int $userId): bool
    {
        $container = Container::getInstance();
        $factory = $container->getFactory($entityTypeId);
        if ($entityId <= 0 || $userId <= 0 || !$factory || !$factory->isObserversEnabled()) {
            return false;
        }

        $item = $factory->getItem($entityId);
        if (!$item) {
            return false;
        }

        $observers = array_map('intval', (array) $item->get(Item::FIELD_NAME_OBSERVERS));
        if (
            in_array($userId, $observers, true)
            || $container->getUserPermissions($userId)->item()->canRead($entityTypeId, $entityId)
        ) {
            return false;
        }

        $observers[] = $userId;
        $item->set(Item::FIELD_NAME_OBSERVERS, $observers);

        // наблюдателя добавляет система: права текущего сотрудника, обязательные поля и роботы не проверяются
        $operation = $factory->getUpdateOperation($item);
        $operation->disableAllChecks();
        $operation->disableAutomation();
        $operation->disableBizProc();

        return $operation->launch()->isSuccess();
    }
}
