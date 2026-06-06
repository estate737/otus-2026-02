<?php
// composer
if (file_exists(__DIR__ . '/../../vendor/autoload.php'))
{
    require_once(__DIR__ . '/../../vendor/autoload.php');
}

// App
if (file_exists(__DIR__ . '/../App/autoload.php'))
{
    require_once(__DIR__ . '/../App/autoload.php');
}

// Регистрация пользовательского типа свойства инфоблока "Процедуры врача"
\Bitrix\Main\EventManager::getInstance()->addEventHandler(
    'iblock',
    'OnIBlockPropertyBuildList',
    [\App\Iblock\Property\DoctorProceduresProperty::class, 'getUserTypeDescription']
);

// JS-расширение виджета записи (догружается в AJAX-гридах универсальных списков)
if (class_exists('CJSCore'))
{
    \CJSCore::RegisterExt('otus.booking', [
        'js' => '/local/js/booking.js',
        'rel' => ['popup', 'ajax'],
    ]);
}

// ДЗ #8: подключение кастомных JS и CSS
AddEventHandler("main", "OnEpilog", "AddCustomJsAndCss");
function AddCustomJsAndCss()
{
    if (defined("ADMIN_SECTION") && ADMIN_SECTION === true)
    {
        return;
    }
    \CJSCore::Init(["popup"]);
    \Bitrix\Main\Page\Asset::getInstance()->addCss("/local/addition/main.css");
    \Bitrix\Main\Page\Asset::getInstance()->addJs("/local/addition/main.js");
}

// вывод данных
function pr($var, $type = false) {
    echo '<pre style="font-size:10px; border:1px solid #000; background:#FFF; text-align:left; color:#000;">';
    if ($type)
        var_dump($var);
    else
        print_r($var);
    echo '</pre>';
}
