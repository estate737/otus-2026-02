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

// ДЗ #9: автозапуск бизнес-процесса при создании элемента списка "Заявки"
\Bitrix\Main\EventManager::getInstance()->addEventHandler(
    'iblock',
    'OnAfterIBlockElementAdd',
    'otusHomework9AutoStartWorkflow'
);

/**
 * Запускает бизнес-процесс над только что созданным элементом списка "Заявки".
 *
 * @param array $arFields поля добавленного элемента (содержат ID и IBLOCK_ID)
 * @return void
 */
function otusHomework9AutoStartWorkflow(array &$arFields): void
{
    $elementId = (int) ($arFields['ID'] ?? 0);
    $iblockId = (int) ($arFields['IBLOCK_ID'] ?? 0);
    if ($elementId <= 0 || $iblockId <= 0)
    {
        return;
    }

    static $ordersIblockId = null;
    if ($ordersIblockId === null)
    {
        $row = \CIBlock::GetList([], ['CODE' => 'orders', 'CHECK_PERMISSIONS' => 'N'])->Fetch();
        $ordersIblockId = (int) ($row['ID'] ?? 0);
    }

    if ($ordersIblockId <= 0 || $iblockId !== $ordersIblockId)
    {
        return;
    }

    if (!\Bitrix\Main\Loader::includeModule('bizproc'))
    {
        return;
    }

    $errors = [];
    \CBPDocument::AutoStartWorkflows(
        ['iblock', 'CIBlockDocument', 'iblock_' . $ordersIblockId],
        \CBPDocumentEventType::Create,
        ['iblock', 'CIBlockDocument', $elementId],
        [],
        $errors
    );
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

// вывод данных
function pr($var, $type = false) {
    echo '<pre style="font-size:10px; border:1px solid #000; background:#FFF; text-align:left; color:#000;">';
    if ($type)
        var_dump($var);
    else
        print_r($var);
    echo '</pre>';
}
