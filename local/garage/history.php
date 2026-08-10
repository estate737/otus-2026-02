<?php
/**
 * История обслуживания автомобиля для всплывающего окна гаража (JSON).
 */

define('PUBLIC_AJAX_MODE', true);
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use App\Service\GarageService;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

header('Content-Type: application/json; charset=utf-8');

global $USER;
if (!is_object($USER) || !$USER->IsAuthorized())
{
    echo json_encode(['error' => Loc::getMessage('SERVICE_GARAGE_ERROR_AUTH')], JSON_UNESCAPED_UNICODE);

    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';

    return;
}

$carId = (int) ($_REQUEST['carId'] ?? 0);
$service = new GarageService();
$car = $service->getCar($carId);

if ($car === null)
{
    echo json_encode(['error' => Loc::getMessage('SERVICE_GARAGE_ERROR_CAR')], JSON_UNESCAPED_UNICODE);

    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';

    return;
}

$contactName = '';
if ($car['CONTACT_ID'] > 0 && \Bitrix\Main\Loader::includeModule('crm'))
{
    $contact = \CCrmContact::GetListEx(
        [],
        ['=ID' => $car['CONTACT_ID'], 'CHECK_PERMISSIONS' => 'N'],
        false,
        false,
        ['ID', 'NAME', 'LAST_NAME']
    )->Fetch();
    if ($contact)
    {
        $contactName = trim($contact['NAME'] . ' ' . $contact['LAST_NAME']);
    }
}

echo json_encode([
    'car' => [
        'id' => $car['ID'],
        'title' => $car['TITLE'],
        'number' => $car['NUMBER'],
        'contactId' => $car['CONTACT_ID'],
        'contactName' => $contactName,
    ],
    'deals' => $service->getServiceHistory($carId),
], JSON_UNESCAPED_UNICODE);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
