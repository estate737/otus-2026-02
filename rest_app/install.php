<?php
/**
 * Установщик локального REST-приложения.
 *
 * Создаёт пользовательское поле контакта (если отсутствует) и подписывает
 * обработчик на событие создания дела. Идемпотентен, запускается из браузера
 * под админом либо из CLI.
 */

require_once __DIR__ . '/crest/crest.php';
require_once __DIR__ . '/lib/LastCommunicationUpdater.php';

$messages = require __DIR__ . '/lang/ru.php';

header('Content-Type: text/plain; charset=utf-8');

$handlerUrl = 'https://cr677203.tw1.ru/rest_app/handler.php';
$field = LastCommunicationUpdater::CONTACT_FIELD;

// 1. Пользовательское поле контакта.
$list = CRest::call('crm.contact.userfield.list', ['filter' => ['FIELD_NAME' => $field]]);
if (!empty($list['result']))
{
    echo $messages['INSTALL_FIELD_EXISTS'] . " ({$field})\n";
}
else
{
    $add = CRest::call('crm.contact.userfield.add', [
        'fields' => [
            'FIELD_NAME' => $field,
            'USER_TYPE_ID' => 'datetime',
            'XML_ID' => 'UF_CRM_LAST_COMMUNICATION',
            'EDIT_FORM_LABEL' => ['ru' => $messages['FIELD_LABEL']],
            'LIST_COLUMN_LABEL' => ['ru' => $messages['FIELD_LABEL']],
            'LIST_FILTER_LABEL' => ['ru' => $messages['FIELD_LABEL']],
        ],
    ]);
    echo $messages['INSTALL_FIELD_CREATED'] . ': ' . ($add['result'] ?? '?') . "\n";
}

// 2. Подписка на событие создания дела.
$unbind = CRest::call('event.unbind', ['event' => 'ONCRMACTIVITYADD', 'handler' => $handlerUrl]);
$bind = CRest::call('event.bind', ['event' => 'ONCRMACTIVITYADD', 'handler' => $handlerUrl]);

if (!empty($bind['error']))
{
    echo 'event.bind error: ' . $bind['error'] . ' ' . ($bind['error_description'] ?? '') . "\n";
}
else
{
    echo $messages['INSTALL_EVENT_BOUND'] . ' (ONCRMACTIVITYADD -> ' . $handlerUrl . ")\n";
}

echo $messages['INSTALL_DONE'] . "\n";
