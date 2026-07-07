<?php
/**
 * Обработчик события создания дела (ONCRMACTIVITYADD).
 *
 * Битрикс шлёт сюда POST при добавлении дела. Обработчик определяет дело и
 * обновляет дату последней коммуникации у связанных контактов через REST.
 */

require_once __DIR__ . '/crest/crest.php';
require_once __DIR__ . '/lib/LastCommunicationUpdater.php';

$messages = require __DIR__ . '/lang/ru.php';

/**
 * Проверяет, что запрос пришёл от нашего портала (по токену приложения).
 *
 * Токен приложения при первой доставке события сохраняется, далее сверяется.
 *
 * @param string $token токен приложения из запроса
 * @return bool
 */
function dz11_checkToken(string $token): bool
{
    if ($token === '')
    {
        return false;
    }

    $file = __DIR__ . '/crest/app_token.txt';
    if (is_file($file))
    {
        return trim((string) file_get_contents($file)) === $token;
    }

    file_put_contents($file, $token);

    return true;
}

$event = (string) ($_REQUEST['event'] ?? '');
$token = (string) ($_REQUEST['auth']['application_token'] ?? '');

if (!dz11_checkToken($token))
{
    http_response_code(403);
    echo 'forbidden';
    return;
}

if (mb_strtoupper($event) !== 'ONCRMACTIVITYADD')
{
    echo $messages['HANDLER_SKIP'];
    return;
}

$activityId = (int) ($_REQUEST['data']['FIELDS']['ID'] ?? 0);
if ($activityId <= 0)
{
    echo $messages['HANDLER_NO_ACTIVITY'];
    return;
}

$result = (new LastCommunicationUpdater())->handleActivityAdd($activityId);

CRest::setLog(
    ['event' => $event, 'activity' => $activityId, 'result' => $result],
    'handler_activity_add'
);

if ($result['skipped'])
{
    echo $messages['HANDLER_NO_ACTIVITY'];
    return;
}

echo empty($result['updated'])
    ? $messages['HANDLER_NO_CONTACT']
    : $messages['HANDLER_UPDATED'] . ': ' . count($result['updated']);
