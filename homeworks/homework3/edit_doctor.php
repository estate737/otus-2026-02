<?
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Loader;
use Models\Lists\DoctorsPropertyValuesTable as DoctorsTable;
use Models\Lists\ProceduresPropertyValuesTable as ProceduresTable;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("Редактирование врача");
Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
Loader::includeModule('iblock');

$doctorId = (int) $_REQUEST['id'];
if ($doctorId <= 0)
{
    LocalRedirect('/homeworks/homework3/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['NAME']))
{
    $el = new CIBlockElement;
    $el->Update($doctorId, ['NAME' => trim($_POST['NAME'])]);

    CIBlockElement::SetPropertyValuesEx($doctorId, DoctorsTable::IBLOCK_ID, [
        'SPECIALIZATION' => $_POST['SPECIALIZATION'],
        'PROCEDURE_ID' => !empty($_POST['PROCEDURE_ID']) ? $_POST['PROCEDURE_ID'] : false,
    ]);

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
$selectedProcedures = is_array($doctor['PROCEDURE_ID']) ? $doctor['PROCEDURE_ID'] : [];

$procedures = ProceduresTable::query()
    ->setSelect([
        'ID' => 'IBLOCK_ELEMENT_ID',
        'NAME' => 'ELEMENT.NAME',
    ])
    ->fetchAll();
?>

<div class="container-fluid p-0" style="max-width: 600px;">

    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/homeworks/homework3/">ДЗ #3</a></li>
            <li class="breadcrumb-item"><a href="doctor.php?id=<?= $doctorId ?>"><?= htmlspecialchars($doctor['NAME']) ?></a></li>
            <li class="breadcrumb-item active">Редактирование</li>
        </ol>
    </nav>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">Данные врача</div>
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">ФИО</label>
                    <input type="text" name="NAME" class="form-control form-control-lg" value="<?= htmlspecialchars($doctor['NAME']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Специализация</label>
                    <input type="text" name="SPECIALIZATION" class="form-control form-control-lg" value="<?= htmlspecialchars($doctor['SPECIALIZATION']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Процедуры</label>
                    <?php foreach ($procedures as $proc): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="PROCEDURE_ID[]" value="<?= $proc['ID'] ?>" id="edit_proc_<?= $proc['ID'] ?>" <?= in_array($proc['ID'], $selectedProcedures) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="edit_proc_<?= $proc['ID'] ?>"><?= htmlspecialchars($proc['NAME']) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn btn-primary w-100 btn-lg">Сохранить</button>
            </form>
        </div>
    </div>

    <div class="text-center mt-3">
        <a href="/homeworks/homework3/" class="btn btn-outline-secondary">&larr; Назад</a>
    </div>

</div>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
