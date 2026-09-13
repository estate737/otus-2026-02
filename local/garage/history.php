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
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

header('Content-Type: application/json; charset=utf-8');

global $USER;
if (!is_object($USER) || !$USER->IsAuthorized()) {
    echo json_encode(['error' => Loc::getMessage('SERVICE_GARAGE_ERROR_AUTH')], JSON_UNESCAPED_UNICODE);

    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';

    return;
}

$carId = (int) ($_REQUEST['carId'] ?? 0);
$service = new GarageService();
$car = $service->getCar($carId);

if ($car === null) {
    echo json_encode(['error' => Loc::getMessage('SERVICE_GARAGE_ERROR_CAR')], JSON_UNESCAPED_UNICODE);

    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';

    return;
}

$permissions = Loader::includeModule('crm') ? Container::getInstance()->getUserPermissions()->item() : null;
if ($permissions === null || !$permissions->canRead($service->getCarTypeId(), $carId)) {
    echo json_encode(['error' => Loc::getMessage('SERVICE_GARAGE_ERROR_ACCESS')], JSON_UNESCAPED_UNICODE);

    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';

    return;
}

$contactName = '';
if ($car['CONTACT_ID'] > 0) {
    $contact = \CCrmContact::GetListEx(
        [],
        ['=ID' => $car['CONTACT_ID'], 'CHECK_PERMISSIONS' => 'N'],
        false,
        false,
        ['ID', 'NAME', 'LAST_NAME']
    )->Fetch();
    if ($contact) {
        $contactName = trim($contact['NAME'] . ' ' . $contact['LAST_NAME']);
    }
}

// в истории остаются заказ-наряды, которые сотрудник может открыть: механик видит только свои
$deals = array_values(array_filter(
    $service->getServiceHistory($carId),
    static fn (array $deal): bool => $permissions->canRead(\CCrmOwnerType::Deal, (int) $deal['ID'])
));

echo json_encode([
    'car' => [
        'id' => $car['ID'],
        'title' => $car['TITLE'],
        'number' => $car['NUMBER'],
        'contactId' => $car['CONTACT_ID'],
        'contactName' => $contactName,
    ],
    'deals' => $deals,
], JSON_UNESCAPED_UNICODE);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
