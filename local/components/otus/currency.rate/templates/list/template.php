<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @var array $arParams */

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
?>

<div class="card shadow-sm" style="max-width: 500px;">
    <div class="card-header bg-primary text-white">
        <strong><?= Loc::getMessage('CURRENCY_RATE_TITLE') ?></strong>
    </div>
    <div class="card-body">
        <h4 class="mb-3">
            <?= htmlspecialcharsbx($arResult['NAME']) ?>
            <small class="text-muted">[<?= htmlspecialcharsbx($arResult['CURRENCY']) ?>]</small>
        </h4>

        <?php if ($arResult['BASE']): ?>
        <p class="mb-0"><?= Loc::getMessage('CURRENCY_RATE_BASE_NOTE') ?></p>
        <?php else: ?>
        <p class="fs-5 mb-0">
            <strong><?= (int) $arResult['AMOUNT'] ?></strong> <?= htmlspecialcharsbx($arResult['CURRENCY']) ?>
            <span class="text-muted">=</span>
            <strong><?= number_format((float) $arResult['RATE'], 4, '.', ' ') ?></strong>
            <?= Loc::getMessage('CURRENCY_RATE_BASE_CURRENCY') ?>
        </p>
        <?php endif; ?>
    </div>
</div>
