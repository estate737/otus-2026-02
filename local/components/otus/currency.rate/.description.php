<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
    'NAME' => GetMessage('CURRENCY_RATE_NAME'),
    'DESCRIPTION' => GetMessage('CURRENCY_RATE_DESC'),
    'SORT' => 10,
    'CACHE_PATH' => 'Y',
    'PATH' => [
        'ID' => 'otus',
        'NAME' => GetMessage('GROUP_NAME'),
        'CHILD' => [
            'ID' => 'currency',
            'NAME' => GetMessage('CURRENCY_GROUP_NAME'),
        ],
    ],
];
