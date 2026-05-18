<?php

namespace Dev\Crmtab;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Обработчики событий модуля
 *
 * @package Dev\Crmtab
 */
class EventHandler
{
    /** @var string ID нашей вкладки */
    private const TAB_ID = 'crmtab_notes';

    /**
     * Карта типов CRM сущностей в дательном падеже (для заголовка вкладки).
     *
     * @param int $entityTypeId
     * @return string
     */
    public static function getEntityTypeName(int $entityTypeId): string
    {
        $map = [
            1 => Loc::getMessage('CRMTAB_ENTITY_LEAD'),
            2 => Loc::getMessage('CRMTAB_ENTITY_DEAL'),
            3 => Loc::getMessage('CRMTAB_ENTITY_CONTACT'),
            4 => Loc::getMessage('CRMTAB_ENTITY_COMPANY'),
        ];

        return $map[$entityTypeId] ?? Loc::getMessage('CRMTAB_ENTITY_DEFAULT');
    }

    /**
     * Обработчик события crm::onEntityDetailsTabsInitialized.
     * Добавляет в карточку CRM сущности вкладку с нашим компонентом.
     *
     * @param Event $event событие CRM с параметрами entityID, entityTypeID, tabs
     * @return EventResult результат с модифицированным массивом вкладок
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

        $tabs[] = [
            'id' => self::TAB_ID,
            'name' => Loc::getMessage('CRMTAB_TAB_NAME', ['#ENTITY#' => self::getEntityTypeName($entityTypeId)]),
            'enabled' => $entityId > 0,
            'loader' => [
                'serviceUrl' => '/local/modules/dev.crmtab/ajax/lazyload.php'
                    . '?entityId=' . $entityId
                    . '&entityTypeId=' . $entityTypeId
                    . '&site=' . SITE_ID
                    . '&' . bitrix_sessid_get(),
                'componentData' => [
                    'template' => '',
                    'params' => [
                        'ENTITY_ID' => $entityId,
                        'ENTITY_TYPE_ID' => $entityTypeId,
                    ],
                ],
            ],
        ];

        return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs]);
    }
}
