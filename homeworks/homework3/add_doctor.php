<?
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Loader;
use Models\Lists\DoctorsPropertyValuesTable as DoctorsTable;
use Models\Lists\ProceduresPropertyValuesTable as ProceduresTable;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("Новый врач");
Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
Loader::includeModule('iblock');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $data = [
        'NAME' => trim($_POST['NAME']),
        'SPECIALIZATION' => $_POST['SPECIALIZATION'],
    ];

    if (!empty($_POST['PROCEDURE_ID']))
    {
        $data['PROCEDURE_ID'] = $_POST['PROCEDURE_ID'];
    }

    $result = DoctorsTable::add($data);
    if ($result)
    {
        LocalRedirect('/homeworks/homework3/');
    }
    else
    {
        $error = DoctorsTable::getLastError();
    }
}

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
            <li class="breadcrumb-item active">Новый врач</li>
        </ol>
    </nav>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">Данные врача</div>
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">ФИО</label>
                    <input type="text" name="NAME" class="form-control form-control-lg" value="<?= htmlspecialchars($_POST['NAME'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Специализация</label>
                    <input type="text" name="SPECIALIZATION" class="form-control form-control-lg" value="<?= htmlspecialchars($_POST['SPECIALIZATION'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Процедуры</label>
                    <?php foreach ($procedures as $proc): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="PROCEDURE_ID[]" value="<?= $proc['ID'] ?>" id="add_proc_<?= $proc['ID'] ?>" <?= in_array($proc['ID'], $_POST['PROCEDURE_ID'] ?? []) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="add_proc_<?= $proc['ID'] ?>"><?= htmlspecialchars($proc['NAME']) ?></label>
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
