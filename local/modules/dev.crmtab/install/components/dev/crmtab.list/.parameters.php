<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentParameters = [
    'GROUPS' => [],
    'PARAMETERS' => [
        'ENTITY_ID' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('CRMTAB_LIST_PARAM_ENTITY_ID'),
            'TYPE' => 'STRING',
            'DEFAULT' => '0',
        ],
        'ENTITY_TYPE_ID' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('CRMTAB_LIST_PARAM_ENTITY_TYPE_ID'),
            'TYPE' => 'STRING',
            'DEFAULT' => '0',
        ],
        'CACHE_TIME' => ['DEFAULT' => 0],
    ],
];
