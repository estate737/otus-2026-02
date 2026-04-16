<?
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Models\Lists\CarsPropertyValuesTable as Cars;
use Models\Lists\SalonsPropertyValuesTable as Salons;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
Loader::includeModule('iblock');

$connection = Application::getConnection();
$sql = file_get_contents(__DIR__ . '/sql/install.sql');

foreach (explode(';', $sql) as $query)
{
    $query = trim($query);
    if (!empty($query))
    {
        $connection->queryExecute($query);
    }
}

// получаем реальные ID элементов из инфоблоков
$carIds = [];
$res = CIBlockElement::GetList([], ['IBLOCK_ID' => Cars::IBLOCK_ID, 'ACTIVE' => 'Y'], false, false, ['ID']);
while ($row = $res->Fetch())
{
    $carIds[] = $row['ID'];
}

$salonIds = [];
$res = CIBlockElement::GetList([], ['IBLOCK_ID' => Salons::IBLOCK_ID, 'ACTIVE' => 'Y'], false, false, ['ID']);
while ($row = $res->Fetch())
{
    $salonIds[] = $row['ID'];
}

if (!empty($carIds) && !empty($salonIds))
{
    $testData = [
        ['Алексей Иванов', '+7 (999) 123-45-67', 30],
        ['Мария Петрова', '+7 (916) 555-12-34', 45],
        ['Дмитрий Сидоров', '+7 (921) 777-88-99', 60],
        ['Ольга Кузнецова', '+7 (987) 111-22-33', 30],
        ['Николай Белов', '+7 (925) 444-55-66', 90],
    ];

    $sqlHelper = $connection->getSqlHelper();
    foreach ($testData as $i => $row)
    {
        $carId = $carIds[$i % count($carIds)];
        $salonId = $salonIds[$i % count($salonIds)];
        $name = $sqlHelper->forSql($row[0]);
        $phone = $sqlHelper->forSql($row[1]);
        $duration = (int) $row[2];
        $connection->queryExecute(
            "INSERT INTO test_drives (CLIENT_NAME, CLIENT_PHONE, DURATION, CAR_ID, SALON_ID) VALUES ('$name', '$phone', $duration, $carId, $salonId)"
        );
    }
}

LocalRedirect('/homeworks/homework4/');
