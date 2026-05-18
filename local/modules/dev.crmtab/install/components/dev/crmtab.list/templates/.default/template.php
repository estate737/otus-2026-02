<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @var array $arParams */
/** @var CMain $APPLICATION */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

Loc::loadMessages(__FILE__);

Extension::load([
    'main.ui.grid',
    'ui.buttons',
    'ui.forms',
    'ui.alerts',
    'ui.label',
]);

$wrapperId = 'dev-crmtab-' . (int) $arResult['ENTITY_TYPE_ID'] . '-' . (int) $arResult['ENTITY_ID'];
$gridId = 'crmtab_grid_' . (int) $arResult['ENTITY_TYPE_ID'] . '_' . (int) $arResult['ENTITY_ID'];

$columns = [
    ['id' => 'ID', 'name' => 'ID', 'default' => true, 'sort' => 'ID'],
    ['id' => 'TITLE', 'name' => Loc::getMessage('CRMTAB_LIST_TPL_COL_TITLE'), 'default' => true, 'sort' => 'TITLE'],
    ['id' => 'BODY', 'name' => Loc::getMessage('CRMTAB_LIST_TPL_COL_BODY'), 'default' => true],
    ['id' => 'AUTHOR', 'name' => Loc::getMessage('CRMTAB_LIST_TPL_COL_AUTHOR'), 'default' => true],
    ['id' => 'CREATED_AT', 'name' => Loc::getMessage('CRMTAB_LIST_TPL_COL_CREATED_AT'), 'default' => true, 'sort' => 'CREATED_AT'],
];

$deleteCallback = 'devCrmtabDelete_' . (int) $arResult['ENTITY_TYPE_ID'] . '_' . (int) $arResult['ENTITY_ID'];

$rows = [];
foreach ($arResult['NOTES'] as $note)
{
    $createdAt = $note['CREATED_AT'];
    if ($createdAt instanceof \Bitrix\Main\Type\DateTime)
    {
        $createdAt = $createdAt->format('d.m.Y H:i');
    }

    $rows[] = [
        'id' => $note['ID'],
        'data' => [
            'ID' => $note['ID'],
            'TITLE' => htmlspecialcharsbx($note['TITLE']),
            'BODY' => htmlspecialcharsbx((string) $note['BODY']),
            'AUTHOR' => htmlspecialcharsbx((string) $note['AUTHOR']),
            'CREATED_AT' => $createdAt,
        ],
        'actions' => [
            [
                'text' => Loc::getMessage('CRMTAB_LIST_TPL_BTN_DELETE'),
                'onclick' => 'window.' . $deleteCallback . '(' . (int) $note['ID'] . ')',
            ],
        ],
    ];
}
?>

<div id="<?= $wrapperId ?>" class="dev-crmtab-wrapper" style="padding:16px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <strong style="font-size:14px;"><?= Loc::getMessage('CRMTAB_LIST_TPL_HEADER', ['#ENTITY#' => $arResult['ENTITY_NAME']]) ?></strong>
        <span class="ui-label ui-label-light"><?= Loc::getMessage('CRMTAB_LIST_TPL_COUNT', ['#COUNT#' => (int) $arResult['COUNT']]) ?></span>
    </div>

    <form class="dev-crmtab-add-form" style="display:grid;grid-template-columns:1fr 2fr auto;gap:8px;margin-bottom:16px;">
        <div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
            <input type="text" name="TITLE" class="ui-ctl-element" placeholder="<?= Loc::getMessage('CRMTAB_LIST_TPL_PH_TITLE') ?>" required>
        </div>
        <div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
            <input type="text" name="BODY" class="ui-ctl-element" placeholder="<?= Loc::getMessage('CRMTAB_LIST_TPL_PH_BODY') ?>">
        </div>
        <button type="submit" class="ui-btn ui-btn-primary"><?= Loc::getMessage('CRMTAB_LIST_TPL_BTN_ADD') ?></button>
    </form>

    <?php if (!empty($rows)): ?>
        <?php $APPLICATION->IncludeComponent(
            'bitrix:main.ui.grid',
            '',
            [
                'GRID_ID' => $gridId,
                'COLUMNS' => $columns,
                'ROWS' => $rows,
                'SHOW_ROW_CHECKBOXES' => false,
                'SHOW_PAGINATION' => false,
                'SHOW_NAVIGATION_PANEL' => false,
                'SHOW_SELECTED_COUNTER' => false,
                'SHOW_TOTAL_COUNTER' => false,
                'SHOW_PAGESIZE' => false,
                'SHOW_ACTION_PANEL' => false,
                'SHOW_ROW_ACTIONS_MENU' => true,
                'ALLOW_COLUMNS_SORT' => true,
                'ALLOW_COLUMNS_RESIZE' => true,
                'ALLOW_HORIZONTAL_SCROLL' => true,
                'ALLOW_SORT' => true,
                'AJAX_MODE' => 'N',
            ]
        ); ?>
    <?php else: ?>
        <div class="ui-alert ui-alert-warning"><span class="ui-alert-message"><?= Loc::getMessage('CRMTAB_LIST_TPL_EMPTY', ['#ENTITY#' => $arResult['ENTITY_NAME']]) ?></span></div>
    <?php endif; ?>
</div>

<script>
(function() {
    var wrapper = document.getElementById('<?= $wrapperId ?>');
    if (!wrapper) return;

    var entityId = <?= (int) $arResult['ENTITY_ID'] ?>;
    var entityTypeId = <?= (int) $arResult['ENTITY_TYPE_ID'] ?>;

    function showError(response) {
        var msg = (response && response.errors && response.errors[0] && response.errors[0].message) || 'Ошибка';
        alert(msg);
    }

    window['<?= $deleteCallback ?>'] = function(id) {
        if (!confirm('<?= Loc::getMessage('CRMTAB_LIST_TPL_CONFIRM_DELETE') ?>')) return;
        BX.ajax.runComponentAction('dev:crmtab.list', 'delete', {
            mode: 'class',
            data: { id: parseInt(id, 10) }
        }).then(function() { window.location.reload(); }, showError);
    };

    wrapper.querySelector('.dev-crmtab-add-form').addEventListener('submit', function(e) {
        e.preventDefault();
        var form = e.target;
        BX.ajax.runComponentAction('dev:crmtab.list', 'add', {
            mode: 'class',
            data: {
                entityTypeId: entityTypeId,
                entityId: entityId,
                title: form.TITLE.value,
                body: form.BODY.value
            }
        }).then(function() { window.location.reload(); }, showError);
    });
})();
</script>
