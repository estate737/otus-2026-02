<?
use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("ДЗ #5: Разработка собственного компонента");
Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
?><h1 class="mb-4"><? $APPLICATION->ShowTitle() ?></h1>
<div class="alert alert-light border mb-4">
	 Свой компонент <code>otus:currency.rate</code> наследует <code>CBitrixComponent</code>. Один параметр <code>CURRENCY</code> типа <code>LIST</code> формирует выпадающий список валют из справочника <a href="/bitrix/admin/currencies.php" target="_blank">Битрикс</a>. В <code>executeComponent()</code> данные берутся через <code>CurrencyTable</code>, кешируются через <code>startResultCache()</code>. Шаблон выводит название валюты и текущий курс.
</div>
 <?$APPLICATION->IncludeComponent(
	"otus:currency.rate",
	"list",
	Array(
		"CACHE_TIME" => "0",
		"CACHE_TYPE" => "A",
		"CURRENCY" => "USD"
	)
);?>
<div class="card shadow-sm mt-4">
	<div class="card-header bg-dark text-white">
		 Управление
	</div>
	<div class="list-group list-group-flush">
 <a href="/bitrix/admin/currencies.php" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
		Справочник валют <span class="badge bg-primary">добавить / изменить курс</span> </a> <a href="/bitrix/admin/fileman_file_view.php?path=%2Fotus%2Fcurrencies.php&lang=ru" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
		Параметры компонента <span class="badge bg-success">выбор валюты</span> </a>
	</div>
</div>
<div class="card shadow-sm mt-4">
	<div class="card-header bg-dark text-white">
		 Файлы проекта
	</div>
	<div class="list-group list-group-flush">
 <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fcomponents%2Fotus%2Fcurrency.rate%2Fclass.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
		class.php <span class="badge bg-primary">класс компонента</span> </a> <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fcomponents%2Fotus%2Fcurrency.rate%2F.parameters.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
		.parameters.php <span class="badge bg-success">параметры компонента</span> </a> <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fcomponents%2Fotus%2Fcurrency.rate%2F.description.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
		.description.php <span class="badge bg-secondary">описание компонента</span> </a> <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fcomponents%2Fotus%2Fcurrency.rate%2Ftemplates%2Flist%2Ftemplate.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
		templates/list/template.php <span class="badge bg-warning text-dark">шаблон компонента</span> </a> <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fcomponents%2Fotus%2Fcurrency.rate%2Flang%2Fru%2Fclass.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
		lang/ru/ <span class="badge bg-info text-dark">языковые фразы</span> </a>
	</div>
</div>
 <br><? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>