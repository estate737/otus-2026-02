<?
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Models\TestDrivesTable;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #4: Модель данных для таблицы БД");
Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
Loader::includeModule('iblock');

$connection = Application::getConnection();
$tableExists = $connection->isTableExists(TestDrivesTable::getTableName());

$drives = [];
if ($tableExists)
{
    $drives = TestDrivesTable::getList([
        'select' => [
            '*',
            'CAR_ELEMENT',
            'CAR.MODEL',
            'CAR.YEAR',
            'CAR.PRICE',
            'SALON_ELEMENT',
            'SALON.ADDRESS',
            'SALON.CITY',
        ],
        'order' => ['DRIVE_DATE' => 'DESC'],
        'cache' => ['ttl' => 60, 'cache_joins' => true],
    ])->fetchCollection();
}
?>

<h1 class="mb-4"><? $APPLICATION->ShowTitle() ?></h1>

<div class="alert alert-light border mb-4">
    Своя таблица <code>test_drives</code> с тест-драйвами. Модель <code>TestDrivesTable</code> наследует DataManager.
    В таблице есть числовые поля, строковые и связываемые (CAR_ID, SALON_ID).
    Связь с инфоблоками Автомобили и Салоны прописана через Reference в getMap().
    Выборка идёт через getList и fetchCollection, в модели есть валидаторы, на запрос повешено кеширование.
    Таблица создаётся и удаляется через ORM методы <code>createDbTable()</code> и <code>dropTable()</code>.
</div>

<div class="mb-4">
    <?php if ($tableExists): ?>
        <a href="uninstall.php" class="btn btn-outline-danger" onclick="return confirm('Удалить таблицу и все данные?')">Удалить таблицу</a>
    <?php else: ?>
        <a href="install.php" class="btn btn-success">Создать таблицу</a>
    <?php endif; ?>
</div>

<?php if ($tableExists): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <span>Тест-драйвы</span>
        <span class="badge bg-light text-primary"><?= count($drives) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
            <thead class="table-light">
                <tr>
                    <th>Клиент</th>
                    <th>Телефон</th>
                    <th>Автомобиль</th>
                    <th>Салон</th>
                    <th class="text-end">Длительность</th>
                    <th>Дата</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($drives as $drive): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($drive->getClientName()) ?></strong></td>
                    <td><?= htmlspecialchars($drive->getClientPhone()) ?></td>
                    <td>
                        <?php $carElement = $drive->getCarElement(); ?>
                        <?php $carProps = $drive->getCar(); ?>
                        <?= $carElement ? htmlspecialchars($carElement->getName()) : '' ?>
                        <?php if ($carProps): ?>
                        <br>
                        <small class="text-muted">
                            <?= htmlspecialchars($carProps->get('YEAR')) ?> г.,
                            <?= number_format((float)$carProps->get('PRICE'), 0, '', ' ') ?> руб.
                        </small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php $salonElement = $drive->getSalonElement(); ?>
                        <?php $salonProps = $drive->getSalon(); ?>
                        <?= $salonElement ? htmlspecialchars($salonElement->getName()) : '' ?>
                        <?php if ($salonProps): ?>
                        <br>
                        <small class="text-muted">
                            <?= htmlspecialchars($salonProps->get('CITY')) ?>,
                            <?= htmlspecialchars($salonProps->get('ADDRESS')) ?>
                        </small>
                        <?php endif; ?>
                    </td>
                    <td class="text-end"><?= $drive->getDuration() ?> мин.</td>
                    <td><small><?= $drive->getDriveDate() ? $drive->getDriveDate()->format('d.m.Y H:i') : '' ?></small></td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($drives) === 0): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Нет данных</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="alert alert-warning">
    Таблица не создана. Нажмите "Создать таблицу".
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header bg-dark text-white">Файлы проекта</div>
    <div class="list-group list-group-flush">
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FModels%2FTestDrivesTable.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            TestDrivesTable.php
            <span class="badge bg-primary">модель таблицы БД</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FModels%2Flang%2Fru%2FTestDrivesTable.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            lang/ru/TestDrivesTable.php
            <span class="badge bg-secondary">языковые фразы</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FModels%2FLists%2FCarsPropertyValuesTable.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            CarsPropertyValuesTable.php
            <span class="badge bg-success">модель инфоблока Автомобили</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FModels%2FLists%2FSalonsPropertyValuesTable.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            SalonsPropertyValuesTable.php
            <span class="badge bg-success">модель инфоблока Салоны</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Fhomeworks%2Fhomework4%2Fsql%2Finstall.sql&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            sql/install.sql
            <span class="badge bg-warning text-dark">SQL создания таблицы</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Fhomeworks%2Fhomework4%2Fsql%2Funinstall.sql&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            sql/uninstall.sql
            <span class="badge bg-warning text-dark">SQL удаления таблицы</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Fhomeworks%2Fhomework4%2Finstall.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            install.php
            <span class="badge bg-dark">скрипт установки</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Fhomeworks%2Fhomework4%2Funinstall.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            uninstall.php
            <span class="badge bg-dark">скрипт удаления</span>
        </a>
    </div>
</div>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
