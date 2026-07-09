<?php

namespace App\Rest;

use App\Model\RecordTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Rest\RestException;
use CRestServer;

Loc::loadMessages(__FILE__);

/**
 * Собственный scope REST и CRUD-методы для сущности «Запись».
 *
 * Регистрируется на событии rest:OnRestServiceBuildDescription. Каждый метод
 * логирует принятые данные и результат обработки.
 *
 * @package App\Rest
 */
class RecordRestService
{
    /** @var string символьный код собственного scope */
    public const SCOPE = 'dev.record';

    /** @var string путь к файлу лога */
    private const LOG_FILE = '/local/logs/dev_record_rest.log';

    /**
     * Обработчик rest:OnRestServiceBuildDescription: регистрирует scope и методы.
     *
     * @return array описание scope и его REST-методов
     */
    public static function onRestServiceBuildDescription(): array
    {
        return [
            self::SCOPE => [
                self::SCOPE . '.add' => [__CLASS__, 'add'],
                self::SCOPE . '.get' => [__CLASS__, 'get'],
                self::SCOPE . '.list' => [__CLASS__, 'getList'],
                self::SCOPE . '.update' => [__CLASS__, 'update'],
                self::SCOPE . '.delete' => [__CLASS__, 'delete'],
            ],
        ];
    }

    /**
     * Создаёт запись.
     *
     * @param array $params поля записи (fields)
     * @param mixed $navigation служебный параметр постраничной навигации
     * @param CRestServer $server объект REST-сервера
     * @return int ID созданной записи
     * @throws RestException при ошибке валидации
     */
    public static function add(array $params, $navigation, CRestServer $server): int
    {
        $fields = self::extractFields($params);
        $fields['CREATED_AT'] = new DateTime();
        $result = RecordTable::add($fields);

        if (!$result->isSuccess())
        {
            self::log(__FUNCTION__, $params, $result->getErrorMessages());
            throw new RestException(
                implode('; ', $result->getErrorMessages()),
                RestException::ERROR_ARGUMENT,
                CRestServer::STATUS_OK
            );
        }

        $id = (int) $result->getId();
        self::log(__FUNCTION__, $params, ['id' => $id]);

        return $id;
    }

    /**
     * Возвращает запись по идентификатору.
     *
     * @param array $params параметры запроса (id)
     * @param mixed $navigation служебный параметр постраничной навигации
     * @param CRestServer $server объект REST-сервера
     * @return array поля записи
     * @throws RestException если запись не найдена
     */
    public static function get(array $params, $navigation, CRestServer $server): array
    {
        $id = (int) ($params['id'] ?? 0);
        $record = RecordTable::getById($id)->fetch();

        if (!$record)
        {
            self::log(__FUNCTION__, $params, ['error' => 'not found']);
            throw new RestException(
                Loc::getMessage('DEV_RECORD_ERROR_NOT_FOUND'),
                RestException::ERROR_ARGUMENT,
                CRestServer::STATUS_OK
            );
        }

        $record = self::normalize($record);
        self::log(__FUNCTION__, $params, $record);

        return $record;
    }

    /**
     * Возвращает список записей с фильтрацией, сортировкой и выборкой полей.
     *
     * @param array $params параметры запроса (filter, order, select, start)
     * @param mixed $navigation служебный параметр постраничной навигации
     * @param CRestServer $server объект REST-сервера
     * @return array список записей
     */
    public static function getList(array $params, $navigation, CRestServer $server): array
    {
        $records = RecordTable::getList([
            'filter' => (array) ($params['filter'] ?? []),
            'order' => (array) ($params['order'] ?? ['ID' => 'DESC']),
            'select' => !empty($params['select']) ? (array) $params['select'] : ['*'],
            'limit' => 50,
        ])->fetchAll();

        $records = array_map([self::class, 'normalize'], $records);
        self::log(__FUNCTION__, $params, ['count' => count($records)]);

        return $records;
    }

    /**
     * Обновляет запись.
     *
     * @param array $params параметры запроса (id, fields)
     * @param mixed $navigation служебный параметр постраничной навигации
     * @param CRestServer $server объект REST-сервера
     * @return bool признак успешного обновления
     * @throws RestException при ошибке
     */
    public static function update(array $params, $navigation, CRestServer $server): bool
    {
        $id = (int) ($params['id'] ?? 0);
        $fields = self::extractFields($params['fields'] ?? $params);
        unset($fields['ID']);

        if ($id <= 0 || empty($fields))
        {
            throw new RestException(
                Loc::getMessage('DEV_RECORD_ERROR_UPDATE_PARAMS'),
                RestException::ERROR_ARGUMENT,
                CRestServer::STATUS_OK
            );
        }

        $result = RecordTable::update($id, $fields);

        if (!$result->isSuccess())
        {
            self::log(__FUNCTION__, $params, $result->getErrorMessages());
            throw new RestException(
                implode('; ', $result->getErrorMessages()),
                RestException::ERROR_ARGUMENT,
                CRestServer::STATUS_OK
            );
        }

        self::log(__FUNCTION__, $params, ['updated' => $id]);

        return true;
    }

    /**
     * Удаляет запись.
     *
     * @param array $params параметры запроса (id)
     * @param mixed $navigation служебный параметр постраничной навигации
     * @param CRestServer $server объект REST-сервера
     * @return bool признак успешного удаления
     * @throws RestException при ошибке
     */
    public static function delete(array $params, $navigation, CRestServer $server): bool
    {
        $id = (int) ($params['id'] ?? 0);
        $result = RecordTable::delete($id);

        if (!$result->isSuccess())
        {
            self::log(__FUNCTION__, $params, $result->getErrorMessages());
            throw new RestException(
                implode('; ', $result->getErrorMessages()),
                RestException::ERROR_ARGUMENT,
                CRestServer::STATUS_OK
            );
        }

        self::log(__FUNCTION__, $params, ['deleted' => $id]);

        return true;
    }

    /**
     * Приводит поля записи к пригодному для REST виду (дату в строку).
     *
     * @param array $record поля записи
     * @return array
     */
    private static function normalize(array $record): array
    {
        if (($record['CREATED_AT'] ?? null) instanceof DateTime)
        {
            $record['CREATED_AT'] = $record['CREATED_AT']->format('Y-m-d H:i:s');
        }

        return $record;
    }

    /**
     * Оставляет только допустимые для записи поля.
     *
     * @param array $params входные данные
     * @return array отфильтрованные поля
     */
    private static function extractFields(array $params): array
    {
        $allowed = ['TITLE', 'DESCRIPTION'];
        $fields = [];
        foreach ($allowed as $name)
        {
            if (array_key_exists($name, $params))
            {
                $fields[$name] = $params[$name];
            }
        }

        return $fields;
    }

    /**
     * Пишет в лог принятые данные и результат обработки метода.
     *
     * @param string $method имя метода
     * @param array $input принятые данные
     * @param mixed $result результат обработки
     * @return void
     */
    private static function log(string $method, array $input, $result): void
    {
        $line = date('c') . ' ' . self::SCOPE . '.' . $method
            . ' IN: ' . json_encode($input, JSON_UNESCAPED_UNICODE)
            . ' OUT: ' . json_encode($result, JSON_UNESCAPED_UNICODE) . PHP_EOL;

        file_put_contents($_SERVER['DOCUMENT_ROOT'] . self::LOG_FILE, $line, FILE_APPEND);
    }
}
