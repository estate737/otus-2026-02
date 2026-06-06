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

// ДЗ #8: подмена окна "Начать рабочий день" своим попапом.
// Скрипт цепляется ко всем публичным страницам, перехватывает onTimeManWindowOpen
// (старая схема) и click capture по кнопке таймера (новая Vue-схема в Битрикс24).
if (!(defined('ADMIN_SECTION') && ADMIN_SECTION === true))
{
    if (class_exists('CJSCore'))
    {
        \CJSCore::Init(['popup']);
    }
    // addString выводится после prolog, поэтому не попадает в composite-кеш как мусор
    \Bitrix\Main\Page\Asset::getInstance()->addString(
        '<script src="/local/addition/main.js?v=' . filemtime(__DIR__ . '/../addition/main.js') . '"></script>'
    );
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
