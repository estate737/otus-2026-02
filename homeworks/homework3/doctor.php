<?
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Loader;
use Models\Lists\DoctorsPropertyValuesTable as DoctorsTable;
use Models\Lists\ProceduresPropertyValuesTable as ProceduresTable;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("Врач");
Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
Loader::includeModule('iblock');

$doctorId = (int) $_REQUEST['id'];
if ($doctorId <= 0)
{
    LocalRedirect('/homeworks/homework3/');
}

$doctor = DoctorsTable::query()
    ->setSelect([
        'ID' => 'IBLOCK_ELEMENT_ID',
        'NAME' => 'ELEMENT.NAME',
        'SPECIALIZATION',
        'PROCEDURE_ID',
    ])
    ->where('IBLOCK_ELEMENT_ID', $doctorId)
    ->fetchAll();

if (empty($doctor))
{
    LocalRedirect('/homeworks/homework3/');
}

$doctor = $doctor[0];
$APPLICATION->SetTitle($doctor['NAME']);

$procedureIds = $doctor['PROCEDURE_ID'];
$procedures = [];

if (!empty($procedureIds) && is_array($procedureIds))
{
    $procedures = ProceduresTable::query()
        ->setSelect([
            'ID' => 'IBLOCK_ELEMENT_ID',
            'NAME' => 'ELEMENT.NAME',
            'PRICE',
            'DURATION',
        ])
        ->whereIn('IBLOCK_ELEMENT_ID', $procedureIds)
        ->fetchAll();
}
?>

<div class="container-fluid p-0" style="max-width: 700px;">

    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/homeworks/">ДЗ</a></li>
            <li class="breadcrumb-item"><a href="/homeworks/homework3/">ДЗ #3</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($doctor['NAME']) ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="edit_doctor.php?id=<?= $doctor['ID'] ?>" class="btn btn-outline-primary">Изменить данные врача</a>
        <a href="delete.php?id=<?= $doctor['ID'] ?>" class="btn btn-outline-danger" onclick="return confirm('Удалить врача?')">Удалить</a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><?= htmlspecialchars($doctor['NAME']) ?></h5>
        </div>
        <div class="card-body">
            <?php if (!empty($doctor['SPECIALIZATION'])): ?>
            <span class="badge bg-secondary fs-6"><?= htmlspecialchars($doctor['SPECIALIZATION']) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <span>Процедуры</span>
            <span class="badge bg-light text-success"><?= count($procedures) ?></span>
        </div>
        <?php if (!empty($procedures)): ?>
        <ul class="list-group list-group-flush">
            <?php foreach ($procedures as $proc): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><?= htmlspecialchars($proc['NAME']) ?></span>
                <span>
                    <?php if (strlen($proc['PRICE'])): ?>
                    <span class="badge bg-warning text-dark"><?= number_format((float)$proc['PRICE'], 0, '', ' ') ?> руб.</span>
                    <?php endif; ?>
                    <?php if (strlen($proc['DURATION'])): ?>
                    <span class="badge bg-info text-dark"><?= (int)$proc['DURATION'] ?> мин.</span>
                    <?php endif; ?>
                </span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <div class="card-body text-center text-muted py-4">Нет привязанных процедур</div>
        <?php endif; ?>
    </div>

    <a href="/homeworks/homework3/" class="btn btn-outline-secondary">&larr; Назад к списку</a>

</div>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
