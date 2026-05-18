<?
use Bitrix\Main\Loader;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$id = (int) $_REQUEST['id'];

if ($id > 0 && Loader::includeModule('dev.crmtab'))
{
    \Dev\Crmtab\Model\NoteTable::delete($id);
}

LocalRedirect('/homeworks/homework6/');
