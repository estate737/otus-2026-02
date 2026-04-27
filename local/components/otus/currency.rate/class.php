<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;

Loc::loadMessages(__FILE__);

/**
 * Компонент вывода курса валюты
 */
class OtusCurrencyRateComponent extends \CBitrixComponent
{
    /**
     * Подготовка параметров компонента.
     *
     * @param array $arParams входные параметры
     * @return array
     */
    public function onPrepareComponentParams($arParams): array
    {
        $arParams['CURRENCY'] = trim((string)($arParams['CURRENCY'] ?? ''));
        $arParams['CACHE_TIME'] = isset($arParams['CACHE_TIME']) ? (int)$arParams['CACHE_TIME'] : 3600;

        return $arParams;
    }

    /**
     * Получение информации о валюте: код, название, текущий курс.
     *
     * @param string $code код валюты
     * @return array|null
     */
    protected function getCurrencyInfo(string $code): ?array
    {
        if ($code === '')
        {
            return null;
        }

        $row = \Bitrix\Currency\CurrencyTable::getList([
            'select' => [
                'CURRENCY',
                'AMOUNT',
                'CURRENT_BASE_RATE',
                'BASE',
                'FULL_NAME' => 'CURRENT_LANG_FORMAT.FULL_NAME',
            ],
            'filter' => ['=CURRENCY' => $code],
            'limit' => 1,
        ])->fetch();

        return $row ?: null;
    }

    /**
     * Точка входа в компонент.
     *
     * @return void
     */
    public function executeComponent(): void
    {
        try
        {
            if (!Loader::includeModule('currency'))
            {
                throw new SystemException(Loc::getMessage('CURRENCY_RATE_MODULE_NOT_INSTALLED'));
            }

            if ($this->arParams['CURRENCY'] === '')
            {
                throw new SystemException(Loc::getMessage('CURRENCY_RATE_NO_CURRENCY'));
            }

            $info = $this->getCurrencyInfo($this->arParams['CURRENCY']);

            if (!$info)
            {
                throw new SystemException(Loc::getMessage('CURRENCY_RATE_NOT_FOUND'));
            }

            $this->arResult = [
                'CURRENCY' => $info['CURRENCY'],
                'NAME' => $info['FULL_NAME'],
                'AMOUNT' => $info['AMOUNT'],
                'RATE' => $info['CURRENT_BASE_RATE'],
                'BASE' => $info['BASE'] === 'Y',
            ];

            $this->IncludeComponentTemplate();
        }
        catch (SystemException $e)
        {
            ShowError($e->getMessage());
        }
    }
}
