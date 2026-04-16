<?
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Loader;
use Models\Lists\ProceduresPropertyValuesTable as ProceduresTable;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("Редактирование процедуры");
Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
Loader::includeModule('iblock');

$procId = (int) $_REQUEST['id'];
if ($procId <= 0)
{
    LocalRedirect('/homeworks/homework3/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['NAME']))
{
    $el = new CIBlockElement;
    $el->Update($procId, ['NAME' => trim($_POST['NAME'])]);

    CIBlockElement::SetPropertyValuesEx($procId, ProceduresTable::IBLOCK_ID, [
        'PRICE' => $_POST['PRICE'],
        'DURATION' => $_POST['DURATION'],
    ]);

    LocalRedirect('/homeworks/homework3/');
}

$proc = ProceduresTable::query()
    ->setSelect([
        'ID' => 'IBLOCK_ELEMENT_ID',
        'NAME' => 'ELEMENT.NAME',
        'PRICE',
        'DURATION',
    ])
    ->where('IBLOCK_ELEMENT_ID', $procId)
    ->fetchAll();

if (empty($proc))
{
    LocalRedirect('/homeworks/homework3/');
}

$proc = $proc[0];
?>

<div class="container-fluid p-0" style="max-width: 600px;">

    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/homeworks/homework3/">ДЗ #3</a></li>
            <li class="breadcrumb-item active">Редактирование процедуры</li>
        </ol>
    </nav>

    <div class="card shadow-sm">
        <div class="card-header bg-success text-white">Данные процедуры</div>
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Название</label>
                    <input type="text" name="NAME" class="form-control form-control-lg" value="<?= htmlspecialchars($proc['NAME']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Стоимость (руб.)</label>
                    <input type="text" name="PRICE" class="form-control form-control-lg" value="<?= htmlspecialchars($proc['PRICE']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Длительность (мин.)</label>
                    <input type="text" name="DURATION" class="form-control form-control-lg" value="<?= htmlspecialchars($proc['DURATION']) ?>">
                </div>
                <button type="submit" class="btn btn-success w-100 btn-lg">Сохранить</button>
            </form>
        </div>
    </div>

    <div class="text-center mt-3">
        <a href="/homeworks/homework3/" class="btn btn-outline-secondary">&larr; Назад</a>
    </div>

</div>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
