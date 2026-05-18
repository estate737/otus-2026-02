<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;

Loader::includeModule('dev.crmtab');

$entityId = (int) ($_GET['entityId'] ?? 0);
$entityTypeId = (int) ($_GET['entityTypeId'] ?? 0);

global $APPLICATION;

$APPLICATION->IncludeComponent(
    'dev:crmtab.list',
    '',
    [
        'ENTITY_ID' => $entityId,
        'ENTITY_TYPE_ID' => $entityTypeId,
    ]
);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
