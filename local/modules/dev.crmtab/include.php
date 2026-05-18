<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

Loader::registerAutoLoadClasses('dev.crmtab', [
    '\\Dev\\Crmtab\\EventHandler' => 'lib/eventhandler.php',
    '\\Dev\\Crmtab\\Model\\NoteTable' => 'lib/model/notetable.php',
]);
