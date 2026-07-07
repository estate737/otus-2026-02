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

// ДЗ #8: подключение кастомных JS и CSS на всех публичных страницах
if (!(defined("ADMIN_SECTION") && ADMIN_SECTION === true))
{
    \CJSCore::Init(["popup"]);
    $jsMtime = @filemtime(__DIR__ . "/../addition/main.js") ?: time();
    $cssMtime = @filemtime(__DIR__ . "/../addition/main.css") ?: time();
    \Bitrix\Main\Page\Asset::getInstance()->addString(
        '<link rel="stylesheet" href="/local/addition/main.css?v=' . $cssMtime . '">'
    );
    \Bitrix\Main\Page\Asset::getInstance()->addString(
        '<script src="/local/addition/main.js?v=' . $jsMtime . '"></script>'
    );
}

// ДЗ #10: двусторонняя синхронизация Заявок (инфоблок) и Сделок (CRM)
$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->addEventHandler('iblock', 'OnAfterIBlockElementAdd', [\App\Handler\OrderEventHandler::class, 'onAfterAdd']);
$eventManager->addEventHandler('iblock', 'OnAfterIBlockElementUpdate', [\App\Handler\OrderEventHandler::class, 'onAfterUpdate']);
$eventManager->addEventHandler('iblock', 'OnBeforeIBlockElementDelete', [\App\Handler\OrderEventHandler::class, 'onBeforeDelete']);
$eventManager->addEventHandler('crm', 'OnAfterCrmDealAdd', [\App\Handler\DealEventHandler::class, 'onAfterAdd']);
$eventManager->addEventHandler('crm', 'OnAfterCrmDealUpdate', [\App\Handler\DealEventHandler::class, 'onAfterUpdate']);
$eventManager->addEventHandler('crm', 'OnBeforeCrmDealDelete', [\App\Handler\DealEventHandler::class, 'onBeforeDelete']);

// ДЗ #11: при создании дела обновляем дату последней коммуникации у контакта через REST
\Bitrix\Main\EventManager::getInstance()->addEventHandler('crm', 'OnActivityAdd', 'dz11UpdateLastCommunication');

/**
 * Обновляет дату последней коммуникации у связанных контактов при создании дела.
 *
 * Реагирует на локальное событие CRM и меняет данные командой REST (CRest).
 * Событие ловит любое дело: звонок, письмо, сообщение, встречу.
 *
 * @param int $activityId ID созданного дела
 * @param array $activityFields поля дела
 * @return void
 */
function dz11UpdateLastCommunication($activityId, $activityFields): void
{
    $appDir = $_SERVER['DOCUMENT_ROOT'] . '/rest_app';
    if (!is_file($appDir . '/crest/crest.php') || !is_file($appDir . '/lib/LastCommunicationUpdater.php'))
    {
        return;
    }

    require_once $appDir . '/crest/crest.php';
    require_once $appDir . '/lib/LastCommunicationUpdater.php';

    try
    {
        (new LastCommunicationUpdater())->handleActivityAdd((int) $activityId);
    }
    catch (\Throwable $e)
    {
        // Ошибка обновления не должна прерывать создание дела.
    }
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
