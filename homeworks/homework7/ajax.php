<?php

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Context;
use Bitrix\Main\Loader;

header('Content-Type: application/json');

/**
 * Найти ID инфоблока по символьному коду.
 *
 * @param string $code
 * @return int
 */
function otusGetIblockId(string $code): int
{
    $row = CIBlock::GetList([], ['CODE' => $code])->Fetch();
    return $row ? (int) $row['ID'] : 0;
}

$request = Context::getCurrent()->getRequest();

if (!check_bitrix_sessid())
{
    echo json_encode(['status' => 'error', 'message' => 'Неверная сессия']);
    die();
}

if (!Loader::includeModule('iblock'))
{
    echo json_encode(['status' => 'error', 'message' => 'Модуль iblock не подключен']);
    die();
}

$doctorId = (int) $request->getPost('doctorId');
$procedureId = (int) $request->getPost('procedureId');
$procedureName = trim((string) $request->getPost('procedureName'));
$fio = trim((string) $request->getPost('fio'));
$time = trim((string) $request->getPost('time'));

if ($fio === '' || $time === '' || $procedureId <= 0)
{
    echo json_encode(['status' => 'error', 'message' => 'Заполните все поля']);
    die();
}

// Приводим время из ISO (2026-04-29T16:15) к виду 29.04.2026 16:15
$ts = strtotime($time);
if ($ts !== false)
{
    $time = date('d.m.Y H:i', $ts);
}

$bookingIblockId = otusGetIblockId('booking');

// Проверка занятости времени (доп. задание): нет ли уже брони на это время у врача
$existing = CIBlockElement::GetList(
    [],
    [
        'IBLOCK_ID' => $bookingIblockId,
        'PROPERTY_DOCTOR' => $doctorId,
        'PROPERTY_BOOKING_TIME' => $time,
    ],
    false,
    false,
    ['ID']
)->Fetch();

if ($existing)
{
    echo json_encode(['status' => 'error', 'message' => 'На это время врач уже занят']);
    die();
}

$el = new CIBlockElement();
$id = $el->Add([
    'IBLOCK_ID' => $bookingIblockId,
    'NAME' => $fio,
    'ACTIVE' => 'Y',
    'PROPERTY_VALUES' => [
        'BOOKING_TIME' => $time,
        'DOCTOR' => $doctorId,
        'PROCEDURE' => $procedureId,
    ],
]);

if ($id)
{
    echo json_encode(['status' => 'success', 'id' => $id]);
}
else
{
    echo json_encode(['status' => 'error', 'message' => $el->LAST_ERROR]);
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
