<?php

/**
 * Обновление даты последней коммуникации в контакте по событию создания дела.
 *
 * Получает дело через REST (crm.activity.get), находит связанные контакты
 * (владелец, привязки, участники коммуникации) и записывает текущие дату и
 * время в пользовательское поле контакта через crm.contact.update.
 */
class LastCommunicationUpdater
{
    /** @var string код пользовательского поля контакта */
    public const CONTACT_FIELD = 'UF_CRM_LAST_COMM';

    /** @var int идентификатор типа сущности «Контакт» в CRM */
    private const OWNER_TYPE_CONTACT = 3;

    /**
     * Обрабатывает добавление дела: обновляет связанные контакты.
     *
     * @param int $activityId идентификатор дела
     * @return array{updated: int[], skipped: bool} результат обработки
     */
    public function handleActivityAdd(int $activityId): array
    {
        $activity = $this->fetchActivity($activityId);
        if ($activity === null)
        {
            return ['updated' => [], 'skipped' => true];
        }

        $contactIds = $this->extractContactIds($activity);
        $updated = [];
        foreach ($contactIds as $contactId)
        {
            if ($this->touchContact($contactId))
            {
                $updated[] = $contactId;
            }
        }

        return ['updated' => $updated, 'skipped' => false];
    }

    /**
     * Возвращает дело по идентификатору.
     *
     * @param int $activityId идентификатор дела
     * @return array|null поля дела либо null, если дело не найдено
     */
    private function fetchActivity(int $activityId): ?array
    {
        $response = CRest::call('crm.activity.get', ['id' => $activityId]);
        $activity = $response['result'] ?? null;

        return is_array($activity) && !empty($activity) ? $activity : null;
    }

    /**
     * Извлекает идентификаторы связанных с делом контактов.
     *
     * Контакт может быть владельцем дела, значиться в привязках (bindings)
     * либо в участниках коммуникации (communications).
     *
     * @param array $activity поля дела
     * @return int[] уникальные идентификаторы контактов
     */
    private function extractContactIds(array $activity): array
    {
        $ids = [];

        if ((int) ($activity['OWNER_TYPE_ID'] ?? 0) === self::OWNER_TYPE_CONTACT)
        {
            $ids[] = (int) $activity['OWNER_ID'];
        }

        foreach ((array) ($activity['BINDINGS'] ?? []) as $binding)
        {
            if ((int) ($binding['OWNER_TYPE_ID'] ?? 0) === self::OWNER_TYPE_CONTACT)
            {
                $ids[] = (int) $binding['OWNER_ID'];
            }
        }

        foreach ((array) ($activity['COMMUNICATIONS'] ?? []) as $communication)
        {
            if ((int) ($communication['ENTITY_TYPE_ID'] ?? 0) === self::OWNER_TYPE_CONTACT)
            {
                $ids[] = (int) $communication['ENTITY_ID'];
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * Записывает в контакт текущие дату и время последней коммуникации.
     *
     * @param int $contactId идентификатор контакта
     * @return bool была ли запись успешной
     */
    private function touchContact(int $contactId): bool
    {
        $response = CRest::call('crm.contact.update', [
            'id' => $contactId,
            'fields' => [
                self::CONTACT_FIELD => date('c'),
            ],
        ]);

        return !empty($response['result']);
    }
}
