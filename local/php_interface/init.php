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

// ДЗ #8: подмена окна "Начать рабочий день" своим попапом + PushPull-канал.
if (!(defined('ADMIN_SECTION') && ADMIN_SECTION === true))
{
    if (class_exists('CJSCore'))
    {
        \CJSCore::Init(['popup', 'pull', 'ui.notification', 'timeman']);
    }
    if (\Bitrix\Main\Loader::includeModule('pull') && is_object($GLOBALS['USER']) && $GLOBALS['USER']->IsAuthorized())
    {
        \CPullWatch::Add((int) $GLOBALS['USER']->GetID(), 'OTUS_HOMEWORK8_' . (int) $GLOBALS['USER']->GetID());
    }
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
