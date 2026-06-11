<?
use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #9: Бизнес-процесс обработки заявки (DADATA + CRM)");
Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
?>

<h1 class="mb-4"><? $APPLICATION->ShowTitle() ?></h1>

<div class="alert alert-light border mb-4">
    При создании элемента инфоблока <b>Заявки</b> запускается бизнес-процесс, который
    по ИНН получает данные организации из сервиса <b>DADATA</b>, находит или создаёт
    компанию в CRM, записывает её в поле <b>Заказчик</b> элемента и создаёт <b>сделку</b>,
    привязанную к этой компании.
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">Что реализовано</div>
    <div class="card-body">
        <ol class="mb-0">
            <li>Инфоблок <b>Заявки</b> (тип <code>otus_orders</code>) с полями:
                <code>Сумма</code>, <code>Заказчик ИНН</code>, <code>Заказчик</code>, <code>Вид работ</code>.</li>
            <li>Сервис <code>App\Service\Dadata</code> - клиент DADATA (suggest party по ИНН).</li>
            <li>Активити <b>Заказчик по ИНН</b> (<code>GetCompanyByInnActivity</code>): принимает ИНН,
                получает компанию из DADATA, ищет её в CRM по <code>UF_COMPANY_INN</code>, при отсутствии
                создаёт, пишет название в поле Заказчик элемента и возвращает ID компании.</li>
            <li>Активити <b>Создать сделку по заявке</b> (<code>CreateOrderDealActivity</code>):
                по ID компании, виду работ и сумме создаёт сделку CRM и возвращает её ID.</li>
            <li>Бизнес-процесс на событие <b>создания</b> элемента: ИНН -> компания -> запись в элемент -> сделка.</li>
        </ol>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white">Соответствие критериям</div>
    <div class="card-body">
        <ul class="mb-0">
            <li><b>Критерий 1</b> (БП создаёт сделку) - активити <code>CreateOrderDealActivity</code> в составе БП.</li>
            <li><b>Критерий 2</b> (БП через бэкенд-сервис создаёт компанию и привязывает к элементу) -
                <code>GetCompanyByInnActivity</code> + <code>App\Service\Dadata</code>.</li>
            <li><b>Критерий 3</b> (активити по ИНН создаёт компанию и возвращает ID) -
                <code>GetCompanyByInnActivity</code>, переиспользуемое отдельное активити.</li>
        </ul>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-success text-white">Файлы проекта</div>
    <div class="list-group list-group-flush">
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Factivities%2Fcustom%2Fgetcompanybyinnactivity%2Fgetcompanybyinnactivity.php&full_src=Y" class="list-group-item list-group-item-action">
            local/activities/custom/getcompanybyinnactivity/getcompanybyinnactivity.php
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Factivities%2Fcustom%2Fcreateorderdealactivity%2Fcreateorderdealactivity.php&full_src=Y" class="list-group-item list-group-item-action">
            local/activities/custom/createorderdealactivity/createorderdealactivity.php
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FService%2FDadata.php&full_src=Y" class="list-group-item list-group-item-action">
            local/App/Service/Dadata.php
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Fhomeworks%2Fhomework9%2Finstall.php&full_src=Y" class="list-group-item list-group-item-action">
            homeworks/homework9/install.php (установщик инфоблока и БП)
        </a>
    </div>
</div>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
