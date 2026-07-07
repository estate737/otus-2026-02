<?
use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #11: Локальное REST-приложение (дата последней коммуникации)");
Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
?>

<h1 class="mb-4"><? $APPLICATION->ShowTitle() ?></h1>

<div class="alert alert-light border mb-4">
    REST-приложение при создании дела (звонок, письмо, сообщение, встреча) записывает текущие
    дату и время в поле <b>Дата последней коммуникации</b> в карточке связанного контакта.
    Данные меняются командой REST <code>crm.contact.update</code> через входящий вебхук и SDK
    <code>CRest.php</code>. Обработка события создания дела подписана на портале.
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">Что реализовано</div>
    <div class="card-body">
        <ol class="mb-0">
            <li>Пользовательское поле контакта <code>UF_CRM_LAST_COMM</code> (дата/время),
                «Дата последней коммуникации».</li>
            <li>Входящий вебхук: доступ приложения к REST API портала (запись через
                <code>crm.contact.update</code>).</li>
            <li>Приложение <code>rest_app</code> на базе <code>CRest.php</code>:
                <code>LastCommunicationUpdater</code> получает дело (<code>crm.activity.get</code>),
                находит связанные контакты и обновляет поле.</li>
            <li>Подписка на создание дела: обработчик <code>OnActivityAdd</code> вызывает логику
                приложения (ловит звонок, письмо, сообщение, встречу).</li>
        </ol>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-secondary text-white">Как проверить</div>
    <div class="card-body">
        <ol class="mb-0">
            <li>Открыть карточку любого контакта в CRM.</li>
            <li>Создать дело: звонок, письмо или задачу по этому контакту.</li>
            <li>В поле «Дата последней коммуникации» появятся текущие дата и время.</li>
        </ol>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-dark text-white">Файлы проекта</div>
    <div class="list-group list-group-flush">
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Frest_app%2Flib%2FLastCommunicationUpdater.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            rest_app/lib/LastCommunicationUpdater.php
            <span class="badge bg-primary">логика REST</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Frest_app%2Fhandler.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            rest_app/handler.php
            <span class="badge bg-success">обработчик события</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Frest_app%2Finstall.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            rest_app/install.php
            <span class="badge bg-info text-dark">поле + подписка</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fphp_interface%2Finit.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            php_interface/init.php
            <span class="badge bg-secondary">подписка на OnActivityAdd</span>
        </a>
    </div>
</div>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
