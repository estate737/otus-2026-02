<?php

namespace App\Handler;

use App\Service\GarageService;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Event;

/**
 * Автоматическое имя автомобиля: «Марка Модель Госномер».
 *
 * Госномер попадает в название, чтобы в списках и в селекторе заказ-наряда
 * различались одинаковые модели разных клиентов. Название обновляется сразу
 * после сохранения по событиям смарт-процесса onCrmDynamicItemAdd_<ID>
 * и onCrmDynamicItemUpdate_<ID>.
 *
 * @package App\Handler
 */
class CarNamingHandler
{
    /**
     * Обработчик событий создания и изменения автомобиля.
     *
     * @param Event $event событие смарт-процесса «Автомобили»
     * @return void
     */
    public static function onSave(Event $event): void
    {
        self::refreshTitle((int) $event->getParameter('id'));
    }

    /**
     * Приводит название автомобиля к виду «Марка Модель Госномер».
     *
     * Название записывается через ORM таблицы элементов в обход операции CRM,
     * поэтому повторного события сохранения не возникает.
     *
     * @param int $carId идентификатор автомобиля
     * @return void
     */
    private static function refreshTitle(int $carId): void
    {
        $typeId = (new GarageService())->getCarTypeId();
        if ($carId <= 0 || $typeId <= 0) {
            return;
        }

        $factory = Container::getInstance()->getFactory($typeId);
        $item = $factory ? $factory->getItem($carId) : null;
        if ($item === null) {
            return;
        }

        $number = trim((string) $item->get('UF_CRM_CAR_NUMBER'));
        if ($number === '') {
            return;
        }

        $title = implode(' ', array_filter([
            trim((string) $item->get('UF_CRM_CAR_BRAND')),
            trim((string) $item->get('UF_CRM_CAR_MODEL')),
            $number,
        ]));

        if ((string) $item->getTitle() !== $title) {
            $factory->getDataClass()::update($carId, ['TITLE' => $title]);
        }
    }
}
