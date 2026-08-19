<?php
/**
 * Приём автомобиля в ремонт из вкладки «Гараж».
 *
 * Создаёт заказ-наряд (сделку воронки сервиса) с уже заполненными клиентом,
 * автомобилем, названием и стадией «Приемка», затем открывает его карточку.
 * Контроль незакрытых заказов срабатывает штатным обработчиком создания.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use App\Service\GarageService;
use App\Service\OrderService;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

global $USER;

$request = Application::getInstance()->getContext()->getRequest();
$carId = (int) $request->get('carId');
$contactId = (int) $request->get('contactId');

/**
 * Показывает сообщение и завершает работу.
 *
 * @param string $message текст
 * @return void
 */
function garageOrderStop(string $message): void
{
    echo '<div style="font: 14px/1.5 Arial, sans-serif; padding: 24px; color: #333;">'
        . htmlspecialcharsbx($message)
        . '<br><br><a href="javascript:history.back()">' . Loc::getMessage('SERVICE_GARAGE_BACK') . '</a></div>';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
    die();
}

if (!$USER->IsAuthorized() || !check_bitrix_sessid())
{
    garageOrderStop(Loc::getMessage('SERVICE_GARAGE_ORDER_ACCESS'));
}

if (!Loader::includeModule('crm') || $carId <= 0)
{
    garageOrderStop(Loc::getMessage('SERVICE_GARAGE_ORDER_NO_CAR'));
}

$garage = new GarageService();
$car = $garage->getCar($carId);
if ($car === null)
{
    garageOrderStop(Loc::getMessage('SERVICE_GARAGE_ORDER_NO_CAR'));
}

if ($contactId <= 0)
{
    $contactId = (int) $car['CONTACT_ID'];
}

$categoryId = (int) Option::get('main', '~service_center_category', 1);
$title = (new OrderService())->buildTitle($contactId, $carId);

$fields = [
    'TITLE' => $title !== '' ? $title : $car['TITLE'] . ' ' . $car['NUMBER'],
    'CATEGORY_ID' => $categoryId,
    'STAGE_ID' => 'C' . $categoryId . ':NEW',
    'CONTACT_ID' => $contactId,
    'ASSIGNED_BY_ID' => (int) $USER->GetID(),
    'CURRENCY_ID' => 'RUB',
    'OPPORTUNITY' => 0,
    'OPENED' => 'Y',
    GarageService::DEAL_CAR_FIELD => $carId,
];

$deal = new CCrmDeal(true);
$dealId = (int) $deal->Add($fields, true, ['DISABLE_USER_FIELD_CHECK' => true]);

if ($dealId <= 0)
{
    $error = trim(strip_tags((string) $deal->LAST_ERROR));
    garageOrderStop($error !== '' ? $error : Loc::getMessage('SERVICE_GARAGE_ORDER_FAIL'));
}

LocalRedirect('/crm/deal/details/' . $dealId . '/');
