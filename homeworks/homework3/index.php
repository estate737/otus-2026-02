<?
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Loader;
use Models\Lists\DoctorsPropertyValuesTable as DoctorsTable;
use Models\Lists\ProceduresPropertyValuesTable as ProceduresTable;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #3: Списки и модели данных");
Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
Loader::includeModule('iblock');

$doctors = DoctorsTable::query()
    ->setSelect([
        'ID' => 'IBLOCK_ELEMENT_ID',
        'NAME' => 'ELEMENT.NAME',
        'SPECIALIZATION',
    ])
    ->fetchAll();

$procedures = ProceduresTable::query()
    ->setSelect([
        'ID' => 'IBLOCK_ELEMENT_ID',
        'NAME' => 'ELEMENT.NAME',
        'PRICE',
        'DURATION',
    ])
    ->fetchAll();
?>

<h1 class="mb-4"><? $APPLICATION->ShowTitle() ?></h1>

<div class="alert alert-light border mb-4">
    Созданы 2 инфоблока (тип "Списки"): Врачи и Процедуры. Свойства хранятся в отдельных таблицах.
    У врачей множественное свойство, привязка к процедурам.
    Данные получаются через кастомные ORM модели, наследующие абстрактный класс AbstractIblockPropertyValuesTable.
    Реализовано добавление, редактирование и удаление врачей и процедур.
</div>

<div class="row g-4">
<div class="col-md-6">

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <span>Врачи</span>
            <a href="add_doctor.php" class="btn btn-sm btn-light">+ Добавить</a>
        </div>
        <div class="list-group list-group-flush overflow-auto" style="max-height:330px">
        <?php foreach ($doctors as $doctor): ?>
            <div class="list-group-item d-flex justify-content-between align-items-start">
                <a href="doctor.php?id=<?= $doctor['ID'] ?>" class="text-decoration-none flex-grow-1">
                    <div class="fw-bold"><?= htmlspecialchars($doctor['NAME']) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($doctor['SPECIALIZATION']) ?></small>
                </a>
                <div class="ms-2 d-flex gap-1">
                    <a href="edit_doctor.php?id=<?= $doctor['ID'] ?>" class="btn btn-sm btn-outline-secondary" title="Редактировать">&#9998;</a>
                    <a href="delete.php?id=<?= $doctor['ID'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Удалить?')" title="Удалить">&times;</a>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($doctors)): ?>
            <div class="list-group-item text-center text-muted py-4">Нет данных</div>
        <?php endif; ?>
        </div>
    </div>

</div>
<div class="col-md-6">

    <div class="card shadow-sm">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <span>Процедуры</span>
            <a href="add_procedure.php" class="btn btn-sm btn-light">+ Добавить</a>
        </div>
        <div class="list-group list-group-flush overflow-auto" style="max-height:330px">
                <?php foreach ($procedures as $proc): ?>
                <div class="list-group-item d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="fw-bold"><?= htmlspecialchars($proc['NAME']) ?></div>
                        <small class="text-muted">
                            <?= strlen($proc['PRICE']) ? number_format((float)$proc['PRICE'], 0, '', ' ') . ' руб.' : '' ?>
                            <?= strlen($proc['DURATION']) ? '/ ' . (int)$proc['DURATION'] . ' мин.' : '' ?>
                        </small>
                    </div>
                    <div class="ms-2 d-flex gap-1">
                        <a href="edit_procedure.php?id=<?= $proc['ID'] ?>" class="btn btn-sm btn-outline-secondary" title="Редактировать">&#9998;</a>
                        <a href="delete.php?id=<?= $proc['ID'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Удалить?')">&times;</a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($procedures)): ?>
                <div class="list-group-item text-center text-muted py-4">Нет данных</div>
                <?php endif; ?>
            </div>
    </div>

</div>
</div>

<div class="card shadow-sm mt-4">
    <div class="card-header bg-dark text-white">Файлы проекта</div>
    <div class="list-group list-group-flush overflow-auto" style="max-height:330px">
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FModels%2FAbstractIblockPropertyValuesTable.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            AbstractIblockPropertyValuesTable.php
            <span class="badge bg-secondary">абстрактный класс</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FModels%2FLists%2FDoctorsPropertyValuesTable.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            DoctorsPropertyValuesTable.php
            <span class="badge bg-primary">модель врачей</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FModels%2FLists%2FProceduresPropertyValuesTable.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            ProceduresPropertyValuesTable.php
            <span class="badge bg-success">модель процедур</span>
        </a>
    </div>
</div>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
