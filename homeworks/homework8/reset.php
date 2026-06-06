<?
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_CHECK', true);
define('STOP_STATISTICS', true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Application;
use Bitrix\Main\Loader;

global $USER;

if (!$USER->IsAuthorized() || !check_bitrix_sessid())
{
    LocalRedirect('/homeworks/homework8/?reset=fail');
}

$userId = (int) $USER->GetID();
$message = 'fail';

if ($userId > 0 && Loader::includeModule('timeman'))
{
    $connection = Application::getConnection();
    $lastId = (int) $connection->queryScalar(
        "SELECT ID FROM b_timeman_entries WHERE USER_ID = " . $userId . " ORDER BY ID DESC"
    );

    if ($lastId > 0)
    {
        $connection->queryExecute("DELETE FROM b_timeman_entries WHERE ID = " . $lastId);
        $message = 'ok';
    }
    else
    {
        $message = 'empty';
    }
}

LocalRedirect('/homeworks/homework8/?reset=' . $message);
