<?php

namespace App\Service;

use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;

Loc::loadMessages(__FILE__);

/**
 * Сервис работы с компаниями CRM на основе данных сервиса DADATA.
 *
 * Ищет компанию по ИНН в реквизитах CRM, при отсутствии создаёт её
 * с автоматическим заполнением реквизитов (ИНН, КПП, ОГРН, наименование).
 *
 * @package App\Service
 */
class CompanyService
{
    /** @var string XML_ID стандартного пресета реквизитов "Организация" */
    private const ORG_PRESET_XML_ID = '#CRM_REQUISITE_PRESET_DEF_ORG#';

    /** @var int ответственный за компанию по умолчанию */
    private const DEFAULT_ASSIGNED_BY_ID = 1;

    /** @var Dadata клиент сервиса DADATA */
    private Dadata $dadata;

    /**
     * @param string $token API-токен DADATA
     * @param string $secret секретный ключ DADATA
     * @throws SystemException если модуль crm недоступен
     */
    public function __construct(string $token, string $secret = '')
    {
        if (!Loader::includeModule('crm'))
        {
            throw new SystemException(Loc::getMessage('COMPANY_SERVICE_ERROR_NO_CRM'));
        }

        $this->dadata = new Dadata($token, $secret);
    }

    /**
     * Возвращает компанию CRM по ИНН, при отсутствии создаёт её
     * с реквизитами из сервиса DADATA.
     *
     * @param string $inn ИНН организации
     * @param int $responsibleId ID ответственного пользователя
     * @return array{ID: int, TITLE: string, IS_NEW: bool}
     * @throws SystemException
     */
    public function getOrCreateByInn(string $inn, int $responsibleId = self::DEFAULT_ASSIGNED_BY_ID): array
    {
        $inn = (string) preg_replace('/\D+/', '', $inn);
        if ($inn === '')
        {
            throw new SystemException(Loc::getMessage('COMPANY_SERVICE_ERROR_EMPTY_INN'));
        }

        $companyId = $this->findCompanyIdByInn($inn);
        if ($companyId !== null)
        {
            return [
                'ID' => $companyId,
                'TITLE' => $this->getCompanyTitle($companyId),
                'IS_NEW' => false,
            ];
        }

        $party = $this->dadata->findPartyByInn($inn);
        if ($party === null)
        {
            throw new SystemException(Loc::getMessage('COMPANY_SERVICE_ERROR_NOT_FOUND', ['#INN#' => $inn]));
        }

        $companyId = $this->createCompany($party, $responsibleId);
        $this->addRequisite($companyId, $party);

        return [
            'ID' => $companyId,
            'TITLE' => $party['shortName'] ?: $party['name'],
            'IS_NEW' => true,
        ];
    }

    /**
     * Ищет компанию CRM по ИНН в реквизитах.
     *
     * @param string $inn ИНН организации
     * @return int|null ID компании либо null, если компания не найдена
     */
    public function findCompanyIdByInn(string $inn): ?int
    {
        $requisite = new EntityRequisite();
        $row = $requisite->getList([
            'filter' => [
                '=ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
                '=RQ_INN' => $inn,
            ],
            'select' => ['ENTITY_ID'],
            'limit' => 1,
        ])->fetch();

        return $row ? (int) $row['ENTITY_ID'] : null;
    }

    /**
     * Возвращает название компании CRM.
     *
     * @param int $companyId ID компании
     * @return string
     */
    private function getCompanyTitle(int $companyId): string
    {
        $row = \CCrmCompany::GetListEx(
            [],
            ['=ID' => $companyId, 'CHECK_PERMISSIONS' => 'N'],
            false,
            false,
            ['ID', 'TITLE']
        )->Fetch();

        return (string) ($row['TITLE'] ?? '');
    }

    /**
     * Создаёт компанию CRM по данным организации из DADATA.
     *
     * @param array $party нормализованные данные организации из DADATA
     * @param int $responsibleId ID ответственного пользователя
     * @return int ID созданной компании
     * @throws SystemException если компанию создать не удалось
     */
    private function createCompany(array $party, int $responsibleId = self::DEFAULT_ASSIGNED_BY_ID): int
    {
        $fields = [
            'TITLE' => $party['shortName'] ?: $party['name'],
            'COMPANY_TYPE' => 'CUSTOMER',
            'OPENED' => 'Y',
            'ASSIGNED_BY_ID' => $responsibleId > 0 ? $responsibleId : self::DEFAULT_ASSIGNED_BY_ID,
        ];

        if (!empty($party['address']))
        {
            $fields['ADDRESS_LEGAL'] = $party['address'];
        }

        $company = new \CCrmCompany(false);
        $companyId = (int) $company->Add($fields, true, ['DISABLE_USER_FIELD_CHECK' => true]);
        if ($companyId <= 0)
        {
            throw new SystemException(
                Loc::getMessage('COMPANY_SERVICE_ERROR_CREATE', ['#ERROR#' => (string) $company->LAST_ERROR])
            );
        }

        return $companyId;
    }

    /**
     * Добавляет компании реквизит с данными организации из DADATA.
     *
     * @param int $companyId ID компании
     * @param array $party нормализованные данные организации из DADATA
     * @return void
     * @throws SystemException если реквизит сохранить не удалось
     */
    private function addRequisite(int $companyId, array $party): void
    {
        $requisite = new EntityRequisite();
        $result = $requisite->add([
            'ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
            'ENTITY_ID' => $companyId,
            'PRESET_ID' => $this->getOrgPresetId(),
            'NAME' => $party['shortName'] ?: $party['name'],
            'ACTIVE' => 'Y',
            'SORT' => 500,
            'RQ_INN' => $party['inn'],
            'RQ_KPP' => $party['kpp'],
            'RQ_OGRN' => $party['ogrn'],
            'RQ_COMPANY_NAME' => $party['shortName'] ?: $party['name'],
            'RQ_COMPANY_FULL_NAME' => $party['fullName'],
        ]);

        if (!$result->isSuccess())
        {
            throw new SystemException(
                Loc::getMessage('COMPANY_SERVICE_ERROR_REQUISITE', [
                    '#ERROR#' => implode('; ', $result->getErrorMessages()),
                ])
            );
        }
    }

    /**
     * Возвращает ID пресета реквизитов "Организация".
     *
     * @return int
     */
    private function getOrgPresetId(): int
    {
        $preset = new EntityPreset();
        $row = $preset->getList([
            'filter' => [
                'ENTITY_TYPE_ID' => EntityPreset::Requisite,
                '=XML_ID' => self::ORG_PRESET_XML_ID,
            ],
            'select' => ['ID'],
            'limit' => 1,
        ])->fetch();

        if (!$row)
        {
            $row = $preset->getList([
                'filter' => ['ENTITY_TYPE_ID' => EntityPreset::Requisite],
                'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
                'select' => ['ID'],
                'limit' => 1,
            ])->fetch();
        }

        return $row ? (int) $row['ID'] : 0;
    }
}
