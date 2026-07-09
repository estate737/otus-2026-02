<?
use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #12: Собственные REST-методы (CRUD)");
Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
?>

<h1 class="mb-4"><? $APPLICATION->ShowTitle() ?></h1>

<div class="alert alert-light border mb-4">
    Зарегистрирован собственный scope REST <code>dev.record</code> и CRUD-методы для кастомной
    сущности «Запись» (таблица <code>b_dev_record</code>). Scope регистрируется на событии
    <code>OnRestServiceBuildDescription</code>. Каждый метод логирует принятые данные и результат.
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">REST-методы scope <code>dev.record</code></div>
    <div class="card-body">
        <ul class="mb-0">
            <li><code>dev.record.add</code> - создание записи</li>
            <li><code>dev.record.get</code> - чтение записи по ID</li>
            <li><code>dev.record.list</code> - получение списка записей</li>
            <li><code>dev.record.update</code> - редактирование записи</li>
            <li><code>dev.record.delete</code> - удаление записи</li>
        </ul>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white">Соответствие критериям</div>
    <div class="card-body">
        <ul class="mb-0">
            <li>Обработчик расширения REST-методов зарегистрирован (<code>OnRestServiceBuildDescription</code>).</li>
            <li>Написаны CRUD-методы (создание, чтение, список, редактирование, удаление).</li>
            <li>Создано 5 вебхуков (по одному на операцию), в каждом настроен генератор запросов.</li>
            <li>Подключено логирование принятых данных и результатов (<code>local/logs/dev_record_rest.log</code>).</li>
        </ul>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-success text-white">Файлы проекта</div>
    <div class="list-group list-group-flush">
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FRest%2FRecordRestService.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            local/App/Rest/RecordRestService.php
            <span class="badge bg-primary">scope и CRUD-методы</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FModel%2FRecordTable.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            local/App/Model/RecordTable.php
            <span class="badge bg-info text-dark">ORM-сущность</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fphp_interface%2Finit.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            php_interface/init.php
            <span class="badge bg-secondary">регистрация scope</span>
        </a>
    </div>
</div>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
