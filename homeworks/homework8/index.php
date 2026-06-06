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
    Кастомный JS подписывается на событие <code>onTimeManWindowOpen</code> модуля <code>timeman</code>,
    закрывает штатное окно и показывает собственный <code>BX.PopupWindowManager</code> с произвольным текстом и
    кнопкой подтверждения. Рабочий день стартует только после нажатия на кнопку (повторно дёргается штатный
    механизм с флагом обхода перехвата). Закрытие попапа = отмена начала рабочего дня.
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">Как воспроизвести</div>
    <div class="card-body">
        <ol class="mb-0">
            <li>Нажми на кнопку "Начать рабочий день" (или "Продолжить") в правом верхнем углу портала Битрикс24.</li>
            <li>Откроется кастомный попап с текстом подтверждения.</li>
            <li>Нажми "Начать рабочий день" в попапе - запустится штатный механизм тайм-менеджера.</li>
            <li>Если закрыть попап (крестик / клик вне окна / Esc), начало рабочего дня отменяется.</li>
        </ol>
        <hr>
        <p class="text-muted mb-2">
            Если рабочий день уже начат, штатная кнопка показывает "Поставить на паузу" / "Закончить день",
            и попап ДЗ #8 не сработает. Чтобы протестировать сценарий начала дня заново, удали свою последнюю
            запись b_timeman_entry:
        </p>
        <form method="post" action="/homeworks/homework8/reset.php" onsubmit="return confirm('Удалить текущий рабочий день?')">
            <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">
            <button type="submit" class="btn btn-outline-danger">Сбросить текущий рабочий день</button>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-dark text-white">Файлы проекта</div>
    <div class="list-group list-group-flush">
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Faddition%2Fmain.js&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            local/addition/main.js
            <span class="badge bg-warning text-dark">перехват onTimeManWindowOpen + BX.PopupWindowManager</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fphp_interface%2Finit.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            php_interface/init.php
            <span class="badge bg-secondary">глобальное подключение main.js + CJSCore popup</span>
        </a>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Fhomeworks%2Fhomework8%2Freset.php&full_src=Y" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            homeworks/homework8/reset.php
            <span class="badge bg-danger">сброс текущего рабочего дня (для отладки)</span>
        </a>
    </div>
</div>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
