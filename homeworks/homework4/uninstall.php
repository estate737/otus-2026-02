<?
use Bitrix\Main\Application;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$connection = Application::getConnection();
$sql = file_get_contents(__DIR__ . '/sql/uninstall.sql');

foreach (explode(';', $sql) as $query)
{
    $query = trim($query);
    if (!empty($query))
    {
        $connection->queryExecute($query);
    }
}

LocalRedirect('/homeworks/homework4/');
