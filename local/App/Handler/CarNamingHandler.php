<?php

namespace App\Handler;

use Bitrix\Main\ORM\Event;

/**
 * Автоматическое имя автомобиля: «Марка Модель Госномер».
 *
 * Госномер попадает в название, чтобы в списках и в селекторе заказ-наряда
 * различались одинаковые модели разных клиентов.
 *
 * @package App\Handler
 */
class CarNamingHandler
{
    /**
     * Обработчик ORM-события после добавления автомобиля.
     *
     * @param Event $event событие ORM
     * @return void
     */
    public static function onAfterAdd(Event $event): void
    {
        self::refreshTitle((int) $event->getParameter('id')['ID'] ?? 0);
    }

    /**
     * Обработчик ORM-события после изменения автомобиля.
     *
     * @param Event $event событие ORM
     * @return void
     */
    public static function onAfterUpdate(Event $event): void
    {
        $primary = $event->getParameter('id');
        self::refreshTitle((int) ($primary['ID'] ?? 0));
    }

    /**
     * Приводит название автомобиля к виду «Марка Модель Госномер».
     *
     * Запись обновляется напрямую, чтобы не вызывать повторное событие.
     *
     * @param int $carId идентификатор автомобиля
     * @return void
     */
    private static function refreshTitle(int $carId): void
    {
        if ($carId <= 0 || !\Bitrix\Main\Loader::includeModule('crm'))
        {
            return;
        }

        $garage = new \App\Service\GarageService();
        $typeId = $garage->getCarTypeId();
        if ($typeId <= 0)
        {
            return;
        }

        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($typeId);
        $item = $factory ? $factory->getItem($carId) : null;
        if (!$item)
        {
            return;
        }

        $brand = trim((string) $item->get('UF_CRM_CAR_BRAND'));
        $model = trim((string) $item->get('UF_CRM_CAR_MODEL'));
        $number = trim((string) $item->get('UF_CRM_CAR_NUMBER'));

        if ($number === '')
        {
            return;
        }

        $title = trim($brand . ' ' . $model . ' ' . $number);
        if (trim((string) $item->getTitle()) === $title)
        {
            return;
        }

        $connection = \Bitrix\Main\Application::getConnection();
        $helper = $connection->getSqlHelper();
        $table = $factory->getDataClass()::getTableName();

        $connection->queryExecute(
            'UPDATE ' . $helper->quote($table)
            . " SET TITLE = '" . $helper->forSql($title) . "'"
            . ' WHERE ID = ' . $carId
        );
    }
}
