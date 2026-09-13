<?php

// автозагрузка классов проекта из local/App
if (file_exists(__DIR__ . '/../App/autoload.php')) {
    require_once __DIR__ . '/../App/autoload.php';
}

$eventManager = \Bitrix\Main\EventManager::getInstance();

// вкладка «Гараж» в карточке контакта CRM
$eventManager->addEventHandler(
    'crm',
    'onEntityDetailsTabsInitialized',
    [\App\Handler\GarageTabHandler::class, 'onEntityDetailsTabsInitialized'],
    false,
    50
);

// контроль незакрытых заказ-нарядов по автомобилю
$eventManager->addEventHandler(
    'crm',
    'OnBeforeCrmDealAdd',
    [\App\Handler\DealControlHandler::class, 'onBeforeDealAdd']
);

// события смарт-процессов «Автомобили» (128) и «Заявки на закупку» (129)
$eventManager->addEventHandler('crm', 'onCrmDynamicItemAdd_128', [\App\Handler\CarNamingHandler::class, 'onSave']);
$eventManager->addEventHandler('crm', 'onCrmDynamicItemUpdate_128', [\App\Handler\CarNamingHandler::class, 'onSave']);
$eventManager->addEventHandler('crm', 'onCrmDynamicItemAdd_129', [\App\Handler\PurchaseRequestHandler::class, 'onAdd']);
$eventManager->addEventHandler('crm', 'onCrmDynamicItemUpdate_129', [\App\Handler\PurchaseRequestHandler::class, 'onUpdate']);
