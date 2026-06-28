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
    <a href="/homeworks/homework4/" class="list-group-item list-group-item-action">
        <h5 class="mb-1">ДЗ №4</h5>
        <small class="text-muted">Модель данных для таблицы БД</small>
    </a>
    <a href="/otus/currencies.php" class="list-group-item list-group-item-action">
        <h5 class="mb-1">ДЗ №5</h5>
        <small class="text-muted">Разработка собственного компонента</small>
    </a>
    <a href="/homeworks/homework6/" class="list-group-item list-group-item-action">
        <h5 class="mb-1">ДЗ №6</h5>
        <small class="text-muted">Модуль для CRM</small>
    </a>
    <a href="/homeworks/homework7/" class="list-group-item list-group-item-action">
        <h5 class="mb-1">ДЗ №7</h5>
        <small class="text-muted">Кастомный тип свойства и попап записи</small>
    </a>
    <a href="/homeworks/homework8/" class="list-group-item list-group-item-action">
        <h5 class="mb-1">ДЗ №8</h5>
        <small class="text-muted">Модальное окно начала рабочего дня</small>
    </a>
    <a href="/homeworks/homework9/" class="list-group-item list-group-item-action">
        <h5 class="mb-1">ДЗ №9</h5>
        <small class="text-muted">Бизнес-процесс: заявка -> компания по ИНН и сделка</small>
    </a>
    <a href="/homeworks/homework10/" class="list-group-item list-group-item-action">
        <h5 class="mb-1">ДЗ №10</h5>
        <small class="text-muted">Обработка событий: синхронизация заявок и сделок</small>
    </a>
</div>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
