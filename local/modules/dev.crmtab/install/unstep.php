<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
use Bitrix\Main\Localization\Loc;
?>
<div class="adm-info-message"><?= Loc::getMessage('CRMTAB_UNINSTALL_OK') ?></div>
<form action="<?= $APPLICATION->GetCurPage() ?>">
    <input type="hidden" name="lang" value="<?= LANG ?>"/>
    <input type="submit" name="" value="<?= Loc::getMessage('MOD_BACK') ?>"/>
</form>
