<?php
/**
 * Информационная страница локального REST-приложения.
 */

$messages = require __DIR__ . '/lang/ru.php';
?><!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($messages['APP_TITLE']) ?></title>
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
<div class="container" style="max-width: 720px;">
    <h1 class="mb-4"><?= htmlspecialchars($messages['APP_TITLE']) ?></h1>

    <div class="alert alert-light border">
        <?= htmlspecialchars($messages['APP_DESCRIPTION']) ?>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-primary text-white">Как работает</div>
        <div class="card-body">
            <ol class="mb-0">
                <li>Входящий вебхук даёт приложению доступ к REST API портала.</li>
                <li>Приложение подписано на событие <code>ONCRMACTIVITYADD</code> (создание дела).</li>
                <li>При создании дела (звонок, письмо, сообщение, встреча) обработчик
                    <code>handler.php</code> через REST находит связанный контакт и пишет
                    текущие дату и время в поле <b><?= htmlspecialchars($messages['FIELD_LABEL']) ?></b>.</li>
            </ol>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">Файлы</div>
        <div class="list-group list-group-flush">
            <span class="list-group-item">rest_app/handler.php - обработчик события</span>
            <span class="list-group-item">rest_app/lib/LastCommunicationUpdater.php - логика обновления через REST</span>
            <span class="list-group-item">rest_app/install.php - установщик (поле и подписка на событие)</span>
            <span class="list-group-item">rest_app/crest/ - PHP SDK CRest</span>
        </div>
    </div>
</div>
</body>
</html>
