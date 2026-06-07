<?
use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #8: Модальное окно начала рабочего дня");
Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
?>

<h1 class="mb-4"><? $APPLICATION->ShowTitle() ?></h1>

<div class="alert alert-light border mb-4">
    <code>local/addition/main.js</code> перехватывает клик по кнопке тайм-менеджера
    (id <code>buttonStartDropdownAnchor*</code>, классы <code>tm-control-panel__action</code>,
    <code>task-status-action_*</code>) и показывает свой <code>BX.PopupWindowManager</code>.
    По кнопке попапа исходный клик повторяется с флагом <code>bypass</code>, и штатный
    Vue-обработчик стартует рабочий день. Закрытие попапа = отмена.
</div>

<div class="card shadow-sm">
    <div class="card-header bg-dark text-white">Файлы</div>
    <div class="list-group list-group-flush">
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Faddition%2Fmain.js&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            local/addition/main.js
            <span class="badge bg-warning text-dark">click capture + BX.PopupWindowManager</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Faddition%2Fmain.css&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            local/addition/main.css
            <span class="badge bg-secondary">стили попапа</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fphp_interface%2Finit.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            local/php_interface/init.php
            <span class="badge bg-secondary">подключение main.js и main.css</span>
        </a>
    </div>
</div>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
