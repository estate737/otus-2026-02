<?
use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #9: Бизнес-процесс обработки заявки (DADATA + CRM)");
Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
?>

<h1 class="mb-4"><? $APPLICATION->ShowTitle() ?></h1>

<div class="alert alert-light border mb-4">
    При создании элемента списка <b>Заявки</b> запускается бизнес-процесс, который
    по ИНН получает данные организации из сервиса <b>DADATA</b>, находит или создаёт
    компанию в CRM с заполненными реквизитами, записывает её название в поле
    <b>Заказчик</b> элемента и создаёт <b>сделку</b>, привязанную к этой компании.
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">Что реализовано</div>
    <div class="card-body">
        <ol class="mb-0">
            <li>Универсальный список <b>Заявки</b> (штатный тип <code>lists</code>) с полями:
                <code>Сумма</code>, <code>Заказчик ИНН</code>, <code>Заказчик</code>, <code>Вид работ</code>.</li>
            <li>Сервисы на бэкенде: <code>App\Service\Dadata</code> - клиент DADATA (suggest party по ИНН)
                и <code>App\Service\CompanyService</code> - поиск компании по реквизиту <code>RQ_INN</code>,
                создание компании с реквизитами (ИНН, КПП, ОГРН, наименование) по пресету "Организация".</li>
            <li>Активити <b>Заказчик по ИНН</b> (<code>GetCompanyByInnActivity</code>): принимает ИНН,
                через <code>CompanyService</code> находит компанию в CRM, при отсутствии создаёт её
                и возвращает ID и название компании.</li>
            <li>Шаблон БП привязан к типу документа <code>Bitrix\Lists\BizprocDocumentLists</code>,
                поэтому виден и редактируется в конструкторе списка. Автозапуск при создании элемента.
                Шаги: активити "Заказчик по ИНН", затем штатные "Изменение документа" (поле Заказчик)
                и "Создать сделку".</li>
        </ol>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white">Соответствие критериям</div>
    <div class="card-body">
        <ul class="mb-0">
            <li><b>Критерий 1</b> (БП создаёт сделку) - штатное активити "Создать сделку"
                с суммой из заявки и привязкой к компании.</li>
            <li><b>Критерий 2</b> (БП через бэкенд-сервис создаёт компанию и привязывает к элементу) -
                <code>CompanyService</code> + <code>Dadata</code>, название компании пишется в поле Заказчик.</li>
            <li><b>Критерий 3</b> (активити по ИНН создаёт компанию и возвращает ID) -
                <code>GetCompanyByInnActivity</code>, переиспользуемое отдельное активити.</li>
        </ul>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-secondary text-white">Где смотреть</div>
    <div class="list-group list-group-flush">
        <a href="/services/lists/25/view/0/" class="list-group-item list-group-item-action">
            Список "Заявки"
        </a>
        <a href="/services/lists/25/bp_list/" class="list-group-item list-group-item-action">
            Шаблоны бизнес-процессов списка
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-success text-white">Файлы проекта</div>
    <div class="list-group list-group-flush">
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Factivities%2Fcustom%2Fgetcompanybyinnactivity%2Fgetcompanybyinnactivity.php&full_src=Y" class="list-group-item list-group-item-action">
            local/activities/custom/getcompanybyinnactivity/getcompanybyinnactivity.php
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FService%2FCompanyService.php&full_src=Y" class="list-group-item list-group-item-action">
            local/App/Service/CompanyService.php
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FService%2FDadata.php&full_src=Y" class="list-group-item list-group-item-action">
            local/App/Service/Dadata.php
        </a>
    </div>
</div>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
