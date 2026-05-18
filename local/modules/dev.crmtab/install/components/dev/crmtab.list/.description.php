<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
    'NAME' => GetMessage('CRMTAB_LIST_NAME'),
    'DESCRIPTION' => GetMessage('CRMTAB_LIST_DESC'),
    'SORT' => 10,
    'CACHE_PATH' => 'Y',
    'PATH' => [
        'ID' => 'dev',
        'NAME' => GetMessage('CRMTAB_LIST_GROUP_VENDOR'),
        'CHILD' => [
            'ID' => 'crm',
            'NAME' => GetMessage('CRMTAB_LIST_GROUP_CRM'),
        ],
    ],
];
