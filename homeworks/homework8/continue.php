<?
/**
 * Продолжение рабочего дня после паузы для ДЗ #8.
 * Использует штатный UseCase: Bitrix\Timeman\UseCase\Worktime\Manage\Relaunch\Handler.
 */
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_CHECK', true);
define('STOP_STATISTICS', true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Timeman\Form\Worktime\WorktimeRecordForm;
use Bitrix\Timeman\UseCase\Worktime\Manage;

global $USER;

header('Content-Type: application/json; charset=utf-8');

if (!$USER->IsAuthorized() || !check_bitrix_sessid())
{
    echo json_encode(['status' => 'error', 'message' => 'unauthorized']);
    die();
}

if (!Loader::includeModule('timeman'))
{
    echo json_encode(['status' => 'error', 'message' => 'timeman module unavailable']);
    die();
}

$recordForm = WorktimeRecordForm::createWithEventForm();
$recordForm->userId = (int) $USER->GetID();

if (!$recordForm->validate())
{
    echo json_encode(['status' => 'error', 'message' => 'validation']);
    die();
}

$result = (new Manage\Relaunch\Handler())->handle($recordForm);

if ($result->isSuccess())
{
    echo json_encode(['status' => 'ok']);
}
else
{
    echo json_encode([
        'status' => 'error',
        'message' => implode('; ', $result->getErrorMessages() ?: ['failed']),
    ]);
}
