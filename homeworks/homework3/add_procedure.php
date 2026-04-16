<?
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Loader;
use Models\Lists\ProceduresPropertyValuesTable as ProceduresTable;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("Новая процедура");
Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
Loader::includeModule('iblock');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $result = ProceduresTable::add([
        'NAME' => trim($_POST['NAME']),
        'PRICE' => $_POST['PRICE'],
        'DURATION' => $_POST['DURATION'],
    ]);

    if ($result)
    {
        LocalRedirect('/homeworks/homework3/');
    }
    else
    {
        $error = ProceduresTable::getLastError();
    }
}
?>

<div class="container-fluid p-0" style="max-width: 600px;">

    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/homeworks/homework3/">ДЗ #3</a></li>
            <li class="breadcrumb-item active">Новая процедура</li>
        </ol>
    </nav>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-success text-white">Данные процедуры</div>
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Название</label>
                    <input type="text" name="NAME" class="form-control form-control-lg" value="<?= htmlspecialchars($_POST['NAME'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Стоимость (руб.)</label>
                    <input type="text" name="PRICE" class="form-control form-control-lg" value="<?= htmlspecialchars($_POST['PRICE'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Длительность (мин.)</label>
                    <input type="text" name="DURATION" class="form-control form-control-lg" value="<?= htmlspecialchars($_POST['DURATION'] ?? '') ?>">
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
