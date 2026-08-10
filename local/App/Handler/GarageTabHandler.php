<?php

namespace App\Handler;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Добавляет вкладку «Гараж» в карточку контакта CRM.
 *
 * @package App\Handler
 */
class GarageTabHandler
{
    /** @var string идентификатор вкладки */
    private const TAB_ID = 'service_center_garage';

    /** @var int идентификатор типа сущности «Контакт» */
    private const ENTITY_TYPE_CONTACT = 3;

    /**
     * Обработчик события crm:onEntityDetailsTabsInitialized.
     *
     * @param Event $event событие карточки CRM
     * @return EventResult результат с дополненным списком вкладок
     */
    public static function onEntityDetailsTabsInitialized(Event $event): EventResult
    {
        $entityId = (int) $event->getParameter('entityID');
        $entityTypeId = (int) $event->getParameter('entityTypeID');
        $tabs = $event->getParameter('tabs');

        if (!is_array($tabs))
        {
            $tabs = [];
        }

        if ($entityTypeId !== self::ENTITY_TYPE_CONTACT || $entityId <= 0)
        {
            return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs]);
        }

        $tabs[] = [
            'id' => self::TAB_ID,
            'name' => Loc::getMessage('SERVICE_GARAGE_TAB_NAME'),
            'enabled' => true,
            'loader' => [
                'serviceUrl' => '/local/garage/tab.php'
                    . '?contactId=' . $entityId
                    . '&site=' . SITE_ID
                    . '&' . bitrix_sessid_get(),
                'componentData' => [
                    'template' => '',
                    'params' => [
                        'CONTACT_ID' => $entityId,
                    ],
                ],
            ],
        ];

        return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs]);
    }
}
