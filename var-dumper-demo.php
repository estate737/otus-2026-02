<?php

use Otus\Helper;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

global $APPLICATION;

$APPLICATION->setTitle("Демо работы var dumper");

dump((object) [
    'ДоДо Пицца' => '12345',
    'Такси' => '123123123123'
]);

$iblockCode = 'clients_s1';
dump([
    'iblockId' => Helper::getIblockIdByCode($iblockCode),
    'iblockCode' => $iblockCode, 
]);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
