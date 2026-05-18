<?
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #6: Модуль для CRM");
Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

$moduleId = 'dev.crmtab';
$isInstalled = ModuleManager::isModuleInstalled($moduleId);

$notes = [];
if ($isInstalled && Loader::includeModule($moduleId))
{
    $notes = \Dev\Crmtab\Model\NoteTable::getList([
        'select' => ['ID', 'ENTITY_TYPE_ID', 'ENTITY_ID', 'TITLE', 'BODY', 'AUTHOR', 'CREATED_AT'],
        'order' => ['ID' => 'DESC'],
    ])->fetchAll();
}

$entityTypes = [
    1 => 'Лид',
    2 => 'Сделка',
    3 => 'Контакт',
    4 => 'Компания',
];
?>

<h1 class="mb-4"><? $APPLICATION->ShowTitle() ?></h1>

<div class="alert alert-light border mb-4">
    Собственный модуль <code>dev.crmtab</code> добавляет вкладку <strong>«Заметки»</strong> в карточку любой CRM сущности
    (сделка, контакт, компания, лид). Заголовок внутри вкладки подставляется в зависимости от типа сущности
    (по сделке, по контакту, по компании, по лиду).
    Модуль перехватывает событие <code>crm:onEntityDetailsTabsInitialized</code> и через AJAX подгружает компонент
    <code>dev:crmtab.list</code>, который выводит данные из своей таблицы <code>crmtab_note</code> через
    штатный <code>bitrix:main.ui.grid</code>. Прямо во вкладке доступны добавление и удаление заметок через
    <code>BX.ajax.runComponentAction</code>; автор подставляется автоматически из текущего пользователя.
    Данные таблицы привязаны к CRM сущности по паре <code>(ENTITY_TYPE_ID, ENTITY_ID)</code>.
    При удалении модуля таблица удаляется, обработчик события снимается, вкладка исчезает из карточки CRM.
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <span>Статус модуля</span>
        <?php if ($isInstalled): ?>
            <span class="badge bg-success">установлен</span>
        <?php else: ?>
            <span class="badge bg-warning text-dark">не установлен</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <p class="mb-3">
            ID модуля: <code><?= htmlspecialcharsbx($moduleId) ?></code><br>
            <?php if ($isInstalled): ?>
                Записей в таблице <code>crmtab_note</code>: <strong><?= count($notes) ?></strong>
            <?php endif; ?>
        </p>
        <?php if ($isInstalled): ?>
            <a href="uninstall.php" class="btn btn-outline-danger" onclick="return confirm('Удалить модуль? Таблица и обработчики будут удалены.')">Удалить модуль</a>
            <a href="/crm/deal/details/0/" class="btn btn-primary" target="_blank">Создать сделку</a>
            <a href="/bitrix/admin/partner_modules.php?lang=ru" class="btn btn-outline-secondary" target="_blank">Модули в админке</a>
        <?php else: ?>
            <a href="install.php" class="btn btn-success">Установить модуль</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($isInstalled): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <span>Все заметки в таблице</span>
        <span class="badge bg-light text-info"><?= count($notes) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Тип</th>
                    <th>ID сущности</th>
                    <th>Заголовок</th>
                    <th>Текст</th>
                    <th>Автор</th>
                    <th>Создано</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($notes as $note): ?>
                <?php
                $createdAt = $note['CREATED_AT'];
                if ($createdAt instanceof \Bitrix\Main\Type\DateTime)
                {
                    $createdAt = $createdAt->format('d.m.Y H:i');
                }
                $typeName = $entityTypes[$note['ENTITY_TYPE_ID']] ?? '?';
                $crmUrl = '';
                switch ((int) $note['ENTITY_TYPE_ID']) {
                    case 1: $crmUrl = '/crm/lead/details/' . $note['ENTITY_ID'] . '/'; break;
                    case 2: $crmUrl = '/crm/deal/details/' . $note['ENTITY_ID'] . '/'; break;
                    case 3: $crmUrl = '/crm/contact/details/' . $note['ENTITY_ID'] . '/'; break;
                    case 4: $crmUrl = '/crm/company/details/' . $note['ENTITY_ID'] . '/'; break;
                }
                ?>
                <tr>
                    <td><?= (int) $note['ID'] ?></td>
                    <td><?= htmlspecialcharsbx($typeName) ?></td>
                    <td>
                        <?php if ($crmUrl): ?>
                            <a href="<?= $crmUrl ?>" target="_blank"><?= (int) $note['ENTITY_ID'] ?></a>
                        <?php else: ?>
                            <?= (int) $note['ENTITY_ID'] ?>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= htmlspecialcharsbx($note['TITLE']) ?></strong></td>
                    <td><?= htmlspecialcharsbx((string) $note['BODY']) ?></td>
                    <td><?= htmlspecialcharsbx((string) $note['AUTHOR']) ?></td>
                    <td><small><?= $createdAt ?></small></td>
                    <td>
                        <a href="delete_note.php?id=<?= (int) $note['ID'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Удалить заметку?')">&times;</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($notes)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Нет заметок</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header bg-dark text-white">Файлы модуля</div>
    <div class="list-group list-group-flush">
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fmodules%2Fdev.crmtab%2Finstall%2Findex.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            install/index.php
            <span class="badge bg-primary">установщик</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fmodules%2Fdev.crmtab%2Finclude.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            include.php
            <span class="badge bg-secondary">точка входа, автолоадер</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fmodules%2Fdev.crmtab%2Flib%2Feventhandler.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            lib/eventhandler.php
            <span class="badge bg-success">обработчик onEntityDetailsTabsInitialized</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fmodules%2Fdev.crmtab%2Flib%2Fmodel%2Fnotetable.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            lib/model/notetable.php
            <span class="badge bg-info text-dark">ORM модель</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fmodules%2Fdev.crmtab%2Fajax%2Flazyload.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            ajax/lazyload.php
            <span class="badge bg-warning text-dark">AJAX endpoint вкладки</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fmodules%2Fdev.crmtab%2Finstall%2Fcomponents%2Fdev%2Fcrmtab.list%2Fclass.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            components/dev/crmtab.list/class.php
            <span class="badge bg-primary">компонент</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fmodules%2Fdev.crmtab%2Finstall%2Fcomponents%2Fdev%2Fcrmtab.list%2Ftemplates%2F.default%2Ftemplate.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            components/dev/crmtab.list/templates/.default/template.php
            <span class="badge bg-success">шаблон с main.ui.grid</span>
        </a>
    </div>
</div>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
