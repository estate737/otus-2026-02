<?
/**
 * Возвращает текущее состояние рабочего дня авторизованного пользователя
 * для ДЗ #8. Используется кастомным main.js перед перехватом клика,
 * чтобы не показывать попап над паузой/завершением.
 *
 * Ответ: {status: "OPENED" | "PAUSED" | "CLOSED" | "EMPTY"}.
 */
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_CHECK', true);
define('STOP_STATISTICS', true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Application;

global $USER;

header('Content-Type: application/json; charset=utf-8');

if (!$USER->IsAuthorized())
{
    echo json_encode(['status' => 'EMPTY']);
    die();
}

if (!Loader::includeModule('timeman'))
{
    echo json_encode(['status' => 'EMPTY']);
    die();
}

$userId = (int) $USER->GetID();
$row = Application::getConnection()
    ->query("SELECT CURRENT_STATUS, PAUSED FROM b_timeman_entries WHERE USER_ID = " . $userId . " ORDER BY ID DESC")
    ->fetch();

if (!$row)
{
    echo json_encode(['status' => 'EMPTY']);
    die();
}

if ($row['CURRENT_STATUS'] === 'CLOSED')
{
    echo json_encode(['status' => 'CLOSED']);
    die();
}

if ($row['PAUSED'] === 'Y')
{
    echo json_encode(['status' => 'PAUSED']);
    die();
}

echo json_encode(['status' => $row['CURRENT_STATUS'] ?: 'EMPTY']);
