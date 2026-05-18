<?
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$moduleId = 'dev.crmtab';

if (!ModuleManager::isModuleInstalled($moduleId))
{
    include $_SERVER["DOCUMENT_ROOT"] . '/local/modules/' . $moduleId . '/install/index.php';
    $module = new dev_crmtab();

    ModuleManager::registerModule($module->MODULE_ID);
    Loader::includeModule($module->MODULE_ID);

    $module->InstallDB();
    $module->InstallEvents();
    $module->InstallFiles();
}

LocalRedirect('/homeworks/homework6/');
