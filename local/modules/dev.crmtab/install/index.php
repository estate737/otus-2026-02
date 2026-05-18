<?php

use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

/**
 * Установщик модуля dev.crmtab
 */
class dev_crmtab extends CModule
{
    /** @var string */
    public $MODULE_ID = 'dev.crmtab';
    /** @var string */
    public $MODULE_VERSION;
    /** @var string */
    public $MODULE_VERSION_DATE;
    /** @var string */
    public $MODULE_NAME;
    /** @var string */
    public $MODULE_DESCRIPTION;
    /** @var string */
    public $PARTNER_NAME;
    /** @var string */
    public $PARTNER_URI;

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('CRMTAB_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('CRMTAB_MODULE_DESC');
        $this->PARTNER_NAME = Loc::getMessage('CRMTAB_PARTNER_NAME');
    }

    /**
     * Установка модуля.
     *
     * @return void
     */
    public function DoInstall(): void
    {
        global $USER, $APPLICATION;
        if (!$USER->IsAdmin())
        {
            return;
        }

        ModuleManager::registerModule($this->MODULE_ID);
        Loader::includeModule($this->MODULE_ID);

        $this->InstallDB();
        $this->InstallEvents();
        $this->InstallFiles();

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('CRMTAB_INSTALL_TITLE'),
            __DIR__ . '/step.php'
        );
    }

    /**
     * Удаление модуля.
     *
     * @return void
     */
    public function DoUninstall(): void
    {
        global $USER, $APPLICATION;
        if (!$USER->IsAdmin())
        {
            return;
        }

        $this->UnInstallEvents();
        $this->UnInstallFiles();
        $this->UnInstallDB();

        ModuleManager::unRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('CRMTAB_UNINSTALL_TITLE'),
            __DIR__ . '/unstep.php'
        );
    }

    /**
     * Создание таблицы заметок.
     *
     * @return bool
     */
    public function InstallDB(): bool
    {
        $connection = Application::getConnection();
        if (!$connection->isTableExists('crmtab_note'))
        {
            $connection->queryExecute("
                CREATE TABLE crmtab_note (
                    ID INT(11) NOT NULL AUTO_INCREMENT,
                    ENTITY_TYPE_ID INT(11) NOT NULL,
                    ENTITY_ID INT(11) NOT NULL,
                    TITLE VARCHAR(255) NOT NULL,
                    BODY TEXT,
                    AUTHOR VARCHAR(100),
                    CREATED_AT DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (ID),
                    KEY ix_entity (ENTITY_TYPE_ID, ENTITY_ID)
                )
            ");
        }

        return true;
    }

    /**
     * Удаление таблицы заметок.
     *
     * @return bool
     */
    public function UnInstallDB(): bool
    {
        Application::getConnection()->queryExecute('DROP TABLE IF EXISTS crmtab_note');
        return true;
    }

    /**
     * Регистрация обработчиков событий.
     *
     * @return bool
     */
    public function InstallEvents(): bool
    {
        EventManager::getInstance()->registerEventHandler(
            'crm',
            'onEntityDetailsTabsInitialized',
            $this->MODULE_ID,
            '\\Dev\\Crmtab\\EventHandler',
            'onEntityDetailsTabsInitialized'
        );

        return true;
    }

    /**
     * Снятие обработчиков событий.
     *
     * @return bool
     */
    public function UnInstallEvents(): bool
    {
        EventManager::getInstance()->unRegisterEventHandler(
            'crm',
            'onEntityDetailsTabsInitialized',
            $this->MODULE_ID,
            '\\Dev\\Crmtab\\EventHandler',
            'onEntityDetailsTabsInitialized'
        );

        return true;
    }

    /**
     * Копирование компонентов в /local/components/.
     *
     * @return bool
     */
    public function InstallFiles(): bool
    {
        CopyDirFiles(
            __DIR__ . '/components',
            Application::getDocumentRoot() . '/local/components',
            true,
            true
        );

        return true;
    }

    /**
     * Удаление компонентов из /local/components/.
     *
     * @return bool
     */
    public function UnInstallFiles(): bool
    {
        DeleteDirFilesEx('/local/components/dev/crmtab.list/');
        return true;
    }
}
