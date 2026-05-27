<?php

namespace App\Iblock\Property;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Iblock\PropertyTable;
use CIBlockElement;
use CJSCore;

Loc::loadMessages(__FILE__);

/**
 * Пользовательский тип свойства инфоблока: список процедур врача.
 *
 * Хранит ID врача (базовый тип E), а в выводе показывает все процедуры,
 * привязанные к этому врачу, в виде кликабельных кнопок. По клику открывается
 * попап (BX.PopupWindow) с формой записи пациента.
 *
 * @package App\Iblock\Property
 */
class DoctorProceduresProperty
{
    /** @var string код пользовательского типа свойства */
    public const USER_TYPE = 'doctor_procedures';

    /**
     * Описание пользовательского типа свойства.
     * Подключается к событию iblock:OnIBlockPropertyBuildList.
     *
     * @return array
     */
    public static function getUserTypeDescription(): array
    {
        return [
            'PROPERTY_TYPE' => PropertyTable::TYPE_ELEMENT,
            'USER_TYPE' => self::USER_TYPE,
            'DESCRIPTION' => Loc::getMessage('OTUS_DOCTOR_PROC_DESC'),
            'GetPropertyFieldHtml' => [__CLASS__, 'getPropertyFieldHtml'],
            'GetPublicViewHTML' => [__CLASS__, 'getPublicViewHtml'],
            'GetPublicEditHTML' => [__CLASS__, 'getPropertyFieldHtml'],
            'GetAdminListViewHTML' => [__CLASS__, 'getAdminListViewHtml'],
        ];
    }

    /**
     * Получение процедур, привязанных к врачу.
     *
     * @param int $doctorId ID элемента инфоблока "Врачи"
     * @return array массив [['ID' => int, 'NAME' => string], ...]
     */
    public static function getDoctorProcedures(int $doctorId): array
    {
        if ($doctorId <= 0 || !Loader::includeModule('iblock'))
        {
            return [];
        }

        $procedureIds = [];
        $res = CIBlockElement::GetProperty(
            self::getIblockIdByCode('doctors'),
            $doctorId,
            [],
            ['CODE' => 'PROCEDURE_ID']
        );
        while ($row = $res->Fetch())
        {
            if (!empty($row['VALUE']))
            {
                $procedureIds[] = (int) $row['VALUE'];
            }
        }

        if (empty($procedureIds))
        {
            return [];
        }

        $procedures = [];
        $rs = CIBlockElement::GetList(
            ['NAME' => 'ASC'],
            ['IBLOCK_ID' => self::getIblockIdByCode('procedures'), 'ID' => $procedureIds],
            false,
            false,
            ['ID', 'NAME']
        );
        while ($item = $rs->Fetch())
        {
            $procedures[] = ['ID' => (int) $item['ID'], 'NAME' => $item['NAME']];
        }

        return $procedures;
    }

    /**
     * Получение ID инфоблока по символьному коду (с учётом разных окружений).
     *
     * @param string $code символьный код инфоблока
     * @return int
     */
    protected static function getIblockIdByCode(string $code): int
    {
        $row = \CIBlock::GetList([], ['CODE' => $code])->Fetch();
        return $row ? (int) $row['ID'] : 0;
    }

    /**
     * HTML виджета записи: кнопки процедур врача + попап бронирования.
     *
     * @param int $doctorId ID врача
     * @return string
     */
    public static function renderWidget(int $doctorId): string
    {
        CJSCore::Init(['otus.booking']);

        $procedures = self::getDoctorProcedures($doctorId);

        if (empty($procedures))
        {
            return Loc::getMessage('OTUS_DOCTOR_PROC_EMPTY');
        }

        $html = '<div class="otus-doctor-procedures">';
        foreach ($procedures as $proc)
        {
            $html .= '<a href="javascript:void(0)" class="otus-proc-link"'
                . ' data-doctor-id="' . $doctorId . '"'
                . ' data-procedure-id="' . $proc['ID'] . '"'
                . ' data-procedure-name="' . htmlspecialcharsbx($proc['NAME']) . '"'
                . ' onclick="OtusBooking.openPopup(this)">'
                . htmlspecialcharsbx($proc['NAME'])
                . '</a><br>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Контрол редактирования свойства в админке.
     *
     * @param array $arProperty
     * @param array $value
     * @param array $strHTMLControlName
     * @return string
     */
    public static function getPropertyFieldHtml($arProperty, $value, $strHTMLControlName): string
    {
        $doctorId = (int) ($value['VALUE'] ?? 0);
        $inputName = htmlspecialcharsbx($strHTMLControlName['VALUE']);

        $html = '<input type="text" name="' . $inputName . '" value="' . $doctorId . '"'
            . ' size="8" placeholder="' . Loc::getMessage('OTUS_DOCTOR_PROC_INPUT_PH') . '">';
        $html .= self::renderWidget($doctorId);

        return $html;
    }

    /**
     * Просмотр значения свойства в публичной части.
     *
     * @param array $arProperty
     * @param array $value
     * @param array $strHTMLControlName
     * @return string
     */
    public static function getPublicViewHtml($arProperty, $value, $strHTMLControlName): string
    {
        return self::renderWidget((int) ($value['VALUE'] ?? 0));
    }

    /**
     * Просмотр значения в списке элементов (админка и универсальные списки).
     *
     * @param array $arProperty
     * @param array $value
     * @param array $strHTMLControlName
     * @return string
     */
    public static function getAdminListViewHtml($arProperty, $value, $strHTMLControlName): string
    {
        $doctorId = (int) ($value['VALUE'] ?? 0);
        return $doctorId > 0 ? self::renderWidget($doctorId) : '&nbsp;';
    }
}
