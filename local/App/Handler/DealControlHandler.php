<?php

namespace App\Handler;

use App\Service\GarageService;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Контроль незакрытых заказ-нарядов по автомобилю.
 *
 * При создании нового заказ-наряда проверяет, нет ли по этому же автомобилю
 * незавершённой сделки. Если есть, создание блокируется, а ответственный за
 * открытый заказ-наряд получает уведомление.
 *
 * @package App\Handler
 */
class DealControlHandler
{
    /**
     * Обработчик события crm:OnBeforeCrmDealAdd.
     *
     * @param array $fields поля создаваемой сделки
     * @return bool false, если создание нужно запретить
     */
    public static function onBeforeDealAdd(array &$fields): bool
    {
        $carId = (int) ($fields[GarageService::DEAL_CAR_FIELD] ?? 0);
        if ($carId <= 0)
        {
            return true;
        }

        $garage = new GarageService();
        $openDeals = $garage->getOpenDeals($carId);
        if (empty($openDeals))
        {
            return true;
        }

        $openDeal = $openDeals[0];
        $car = $garage->getCar($carId);
        $carTitle = $car ? $car['TITLE'] . ' ' . $car['NUMBER'] : (string) $carId;

        $message = Loc::getMessage('SERVICE_DEAL_CONTROL_ERROR', [
            '#CAR#' => $carTitle,
            '#DEAL_ID#' => $openDeal['ID'],
            '#DEAL_TITLE#' => $openDeal['TITLE'],
            '#STAGE#' => $openDeal['STAGE_NAME'],
        ]);

        self::notifyResponsible((int) $openDeal['ASSIGNED_BY_ID'], $message);

        // CRM показывает пользователю текст из RESULT_MESSAGE
        $fields['RESULT_MESSAGE'] = $message;

        global $APPLICATION;
        $APPLICATION->ThrowException($message);

        return false;
    }

    /**
     * Уведомляет ответственного за незакрытый заказ-наряд.
     *
     * @param int $userId ответственный сотрудник
     * @param string $message текст уведомления
     * @return void
     */
    private static function notifyResponsible(int $userId, string $message): void
    {
        if ($userId <= 0 || !\Bitrix\Main\Loader::includeModule('im'))
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
}
