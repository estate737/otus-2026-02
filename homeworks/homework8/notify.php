<?
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_CHECK', true);
define('STOP_STATISTICS', true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;

global $USER;

header('Content-Type: application/json; charset=utf-8');

if (!$USER->IsAuthorized() || !check_bitrix_sessid())
{
    echo json_encode(['status' => 'error', 'message' => 'unauthorized']);
    die();
}

if (!Loader::includeModule('pull'))
{
    echo json_encode(['status' => 'error', 'message' => 'pull module unavailable']);
    die();
}

$userId = (int) $USER->GetID();
$action = preg_replace('/[^A-Z]/', '', strtoupper((string) ($_POST['action'] ?? '')));

$message = $action === 'REOPEN'
    ? 'Рабочий день продолжен (PushPull -> ДЗ #8)'
    : 'Рабочий день начат (PushPull -> ДЗ #8)';

\CPullWatch::AddToStack('OTUS_HOMEWORK8_' . $userId, [
    'module_id' => 'otus.homework8',
    'command' => 'workdayConfirmed',
    'params' => [
        'action' => $action,
        'message' => $message,
        'timestamp' => time(),
    ],
]);

\Bitrix\Pull\Event::add($userId, [
    'module_id' => 'otus.homework8',
    'command' => 'workdayConfirmed',
    'params' => [
        'action' => $action,
        'message' => $message,
        'timestamp' => time(),
    ],
]);

echo json_encode(['status' => 'ok', 'userId' => $userId, 'action' => $action]);
