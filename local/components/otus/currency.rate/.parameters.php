<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arCurrentValues */

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$currencies = [];
if (Loader::includeModule('currency'))
{
    $rs = \Bitrix\Currency\CurrencyTable::getList([
        'select' => ['CURRENCY', 'FULL_NAME' => 'CURRENT_LANG_FORMAT.FULL_NAME'],
        'order' => ['SORT' => 'ASC'],
    ]);
    while ($row = $rs->fetch())
    {
        $currencies[$row['CURRENCY']] = '[' . $row['CURRENCY'] . '] ' . $row['FULL_NAME'];
    }
}

$arComponentParameters = [
    'GROUPS' => [],
    'PARAMETERS' => [
        'CURRENCY' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('CURRENCY_RATE_PARAM_CURRENCY'),
            'TYPE' => 'LIST',
            'VALUES' => $currencies,
            'DEFAULT' => 'USD',
            'REFRESH' => 'N',
        ],
        'CACHE_TIME' => ['DEFAULT' => 3600],
    ],
];
