<?
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$moduleId = 'dev.crmtab';

if (ModuleManager::isModuleInstalled($moduleId))
{
    Loader::includeModule($moduleId);

    include $_SERVER["DOCUMENT_ROOT"] . '/local/modules/' . $moduleId . '/install/index.php';
    $module = new dev_crmtab();

    $module->UnInstallEvents();
    $module->UnInstallFiles();
    $module->UnInstallDB();

    ModuleManager::unRegisterModule($module->MODULE_ID);
}

LocalRedirect('/homeworks/homework6/');
