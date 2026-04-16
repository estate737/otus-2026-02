<?
use Bitrix\Main\Loader;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
Loader::includeModule('iblock');

$id = (int) $_REQUEST['id'];
if ($id > 0)
{
    CIBlockElement::Delete($id);
}

LocalRedirect('/homeworks/homework3/');
