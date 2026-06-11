<?php

namespace App\Service;

use Exception;

/**
 * Клиент сервиса DADATA (suggest/clean/findById) без внешних зависимостей.
 *
 * Достаточно расширения curl. Токен и секрет выдаются в личном кабинете
 * https://dadata.ru/. Для поиска организации по ИНН используется метод suggest
 * по типу "party".
 *
 * @package App\Service
 */
class Dadata
{
    /** @var string базовый адрес сервиса подсказок */
    private const SUGGEST_URL = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs';

    /** @var string базовый адрес сервиса очистки данных */
    private const CLEAN_URL = 'https://cleaner.dadata.ru/api/v1/clean';

    /** @var string API-токен */
    private string $token;

    /** @var string секретный ключ (нужен для clean/findById) */
    private string $secret;

    /** @var \CurlHandle|resource|null дескриптор curl */
    private $handle = null;

    /**
     * @param string $token API-токен DADATA
     * @param string $secret секретный ключ DADATA
     */
    public function __construct(string $token, string $secret = '')
    {
        $this->token = $token;
        $this->secret = $secret;
    }

    /**
     * Инициализирует curl-соединение с общими заголовками авторизации.
     *
     * @return void
     */
    public function init(): void
    {
        $this->handle = curl_init();
        curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->handle, CURLOPT_TIMEOUT, 10);
        curl_setopt($this->handle, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Token ' . $this->token,
            'X-Secret: ' . $this->secret,
        ]);
        curl_setopt($this->handle, CURLOPT_POST, 1);
    }

    /**
     * Подсказки по типу сущности (party, address, bank, fio и т.д.).
     *
     * @param string $type тип сущности DADATA
     * @param array $fields тело запроса (query, count, и пр.)
     * @return array
     * @throws Exception
     */
    public function suggest(string $type, array $fields): array
    {
        return $this->executeRequest(self::SUGGEST_URL . "/suggest/{$type}", $fields);
    }

    /**
     * Возвращает первую найденную организацию по ИНН в нормализованном виде.
     *
     * Ключи результата: inn, kpp, name, fullName, address, ogrn, kpp,
     * management. Если организация не найдена либо сервис недоступен,
     * возвращается null.
     *
     * @param string $inn ИНН организации
     * @return array|null
     */
    public function findPartyByInn(string $inn): ?array
    {
        $inn = trim($inn);
        if ($inn === '')
        {
            return null;
        }

        try
        {
            if ($this->handle === null)
            {
                $this->init();
            }
            $response = $this->suggest('party', ['query' => $inn, 'count' => 1]);
        }
        catch (Exception $e)
        {
            return null;
        }

        if (empty($response['suggestions'][0]))
        {
            return null;
        }

        $suggestion = $response['suggestions'][0];
        $data = $suggestion['data'] ?? [];

        return [
            'name' => $suggestion['value'] ?? '',
            'fullName' => $data['name']['full_with_opf'] ?? ($suggestion['value'] ?? ''),
            'shortName' => $data['name']['short_with_opf'] ?? ($suggestion['value'] ?? ''),
            'inn' => $data['inn'] ?? $inn,
            'kpp' => $data['kpp'] ?? '',
            'ogrn' => $data['ogrn'] ?? '',
            'address' => $data['address']['value'] ?? '',
            'management' => $data['management']['name'] ?? '',
        ];
    }

    /**
     * Закрывает curl-соединение.
     *
     * @return void
     */
    public function close(): void
    {
        if ($this->handle !== null)
        {
            curl_close($this->handle);
            $this->handle = null;
        }
    }

    /**
     * Выполняет POST-запрос с JSON-телом и возвращает декодированный ответ.
     *
     * @param string $url адрес метода
     * @param array $fields тело запроса
     * @return array
     * @throws Exception при http-коде, отличном от 200
     */
    private function executeRequest(string $url, array $fields): array
    {
        if ($this->handle === null)
        {
            $this->init();
        }

        curl_setopt($this->handle, CURLOPT_URL, $url);
        curl_setopt($this->handle, CURLOPT_POST, 1);
        curl_setopt($this->handle, CURLOPT_POSTFIELDS, json_encode($fields));

        $result = curl_exec($this->handle);
        $httpCode = (int) curl_getinfo($this->handle, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200)
        {
            throw new Exception('DADATA request failed with http code ' . $httpCode . ': ' . $result);
        }

        return (array) json_decode((string) $result, true);
    }
}
