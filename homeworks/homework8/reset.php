<?
/**
 * Сброс текущего рабочего дня (для отладки попапа из ДЗ #8).
 * Удаляет последнюю запись b_timeman_entries авторизованного пользователя,
 * чтобы штатная кнопка снова показывала "Начать рабочий день".
 */
use Bitrix\Main\Application;
use Bitrix\Main\Loader;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

global $USER;

if (!$USER->IsAuthorized() || !check_bitrix_sessid())
{
    LocalRedirect('/homeworks/homework8/');
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
