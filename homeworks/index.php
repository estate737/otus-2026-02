<?

use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("Домашние задания");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

?>
<h1 class="mb-4"><? $APPLICATION->ShowTitle() ?></h1>

<div class="list-group" style="max-width: 600px;">
    <a href="/var-dumper-demo.php" class="list-group-item list-group-item-action">
        <h5 class="mb-1">ДЗ №1</h5>
        <small class="text-muted">Создание и настройка проекта в VSCode</small>
    </a>
    <a href="/homeworks/homework2/" class="list-group-item list-group-item-action">
        <h5 class="mb-1">ДЗ №2</h5>
        <small class="text-muted">Отладка и логирование</small>
    </a>
    <a href="/homeworks/homework3/" class="list-group-item list-group-item-action">
        <h5 class="mb-1">ДЗ №3</h5>
        <small class="text-muted">Списки и модели данных</small>
    </a>
</div>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
