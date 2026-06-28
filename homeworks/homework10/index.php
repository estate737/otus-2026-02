<?
use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #10: Обработка событий (Заявки и Сделки)");
Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
?>

<h1 class="mb-4"><? $APPLICATION->ShowTitle() ?></h1>

<div class="alert alert-light border mb-4">
    Обработчики событий двусторонне синхронизируют элементы инфоблока <code>requests</code> (Заявки)
    и сделки CRM. При создании и изменении заявки её сумма и ответственный переносятся в связанную сделку
    (события <code>OnAfterIBlockElementAdd</code>, <code>OnAfterIBlockElementUpdate</code>), а при изменении
    сделки те же значения возвращаются в связанные заявки (<code>OnAfterCrmDealAdd</code>,
    <code>OnAfterCrmDealUpdate</code>). При удалении сделки заявки отвязываются
    (<code>OnBeforeCrmDealDelete</code>). Логика вынесена в сервис <code>App\Service\OrderDealSync</code>,
    класс <code>App\Handler\SyncGuard</code> не даёт встречным обработчикам зациклиться.
    Регистрация выполнена в <code>init.php</code> через <code>EventManager</code>.
</div>

<div class="card shadow-sm">
    <div class="card-header bg-dark text-white">Файлы проекта</div>
    <div class="list-group list-group-flush">
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FHandler%2FOrderEventHandler.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            App/Handler/OrderEventHandler.php
            <span class="badge bg-primary">события заявки</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FHandler%2FDealEventHandler.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            App/Handler/DealEventHandler.php
            <span class="badge bg-success">события сделки</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FService%2FOrderDealSync.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            App/Service/OrderDealSync.php
            <span class="badge bg-info text-dark">логика синхронизации</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FHandler%2FSyncGuard.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            App/Handler/SyncGuard.php
            <span class="badge bg-warning text-dark">защита от рекурсии</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fphp_interface%2Finit.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            php_interface/init.php
            <span class="badge bg-secondary">регистрация обработчиков</span>
        </a>
    </div>
</div>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
