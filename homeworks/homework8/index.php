<?
use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #8: Модальное окно начала рабочего дня");
Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
?>

<h1 class="mb-4"><? $APPLICATION->ShowTitle() ?></h1>

<?php $reset = $_GET['reset'] ?? ''; ?>
<?php if ($reset === 'ok'): ?>
    <div class="alert alert-success">Текущий рабочий день удалён. Жми "Начать рабочий день" в правом верхнем углу.</div>
<?php elseif ($reset === 'empty'): ?>
    <div class="alert alert-info">Нет открытых записей рабочего дня.</div>
<?php elseif ($reset === 'fail'): ?>
    <div class="alert alert-danger">Не удалось сбросить рабочий день.</div>
<?php endif; ?>

<div class="alert alert-light border mb-4">
    <strong>Архитектура:</strong>
    <ul class="mb-0">
        <li>Подписка <code>BX.PULL.subscribe({moduleId: 'timeman'})</code> ловит команды
            <code>start/pause/continue/relaunch/stop</code> и держит актуальный статус - не нужно
            опрашивать сервер.</li>
        <li>Click capture на <code>.timeman-work-time-button</code> / <code>#bx_tm</code> перехватывает
            клик, прячет штатное окно (<code>WND.Hide()</code>) и показывает
            <code>BX.PopupWindowManager</code>.</li>
        <li>По кнопке попапа - штатный экшен <code>WND.ACTIONS.OPEN</code> / <code>REOPEN</code>;
            дополнительно AJAX на <code>notify.php</code>, который инициирует своё PushPull-событие
            (<code>moduleId: otus.homework8</code>, command: <code>workdayConfirmed</code>) - фронт
            ловит его и показывает <code>BX.UI.Notification</code>.</li>
        <li>Закрытие попапа = отмена.</li>
    </ul>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">Как воспроизвести</div>
    <div class="card-body">
        <ol class="mb-0">
            <li>Нажми на кнопку "Начать рабочий день" (или "Продолжить") в правом верхнем углу портала.</li>
            <li>Откроется кастомный попап с текстом подтверждения.</li>
            <li>Нажми кнопку в попапе - запустится штатный механизм тайм-менеджера.</li>
            <li>Если закрыть попап (крестик / клик вне окна / Esc), начало дня отменяется.</li>
        </ol>
        <hr>
        <p class="text-muted mb-2">
            Чтобы протестировать сценарий заново, удали свою последнюю запись <code>b_timeman_entries</code>:
        </p>
        <form method="post" action="/homeworks/homework8/reset.php" onsubmit="return confirm('Удалить текущий рабочий день?')">
            <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">
            <button type="submit" class="btn btn-outline-danger">Сбросить текущий рабочий день</button>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-dark text-white">Файлы</div>
    <div class="list-group list-group-flush">
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Faddition%2Fmain.js&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            local/addition/main.js
            <span class="badge bg-warning text-dark">onTimeManWindowOpen + BX.PopupWindowManager</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fphp_interface%2Finit.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            local/php_interface/init.php
            <span class="badge bg-secondary">подключение main.js + CJSCore popup,timeman</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Fhomeworks%2Fhomework8%2Fnotify.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            homeworks/homework8/notify.php
            <span class="badge bg-success">backend -> CPullWatch::AddToStack + Pull\Event::add</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Fhomeworks%2Fhomework8%2Freset.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            homeworks/homework8/reset.php
            <span class="badge bg-danger">сброс рабочего дня (для отладки)</span>
        </a>
    </div>
</div>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
