<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Bizproc\Activity\BaseActivity;
use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Bizproc\FieldType;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use App\Service\Dadata;

/**
 * Активити "Заказчик по ИНН".
 *
 * Принимает ИНН, получает данные организации из сервиса DADATA, ищет компанию
 * в CRM по полю UF_COMPANY_INN и при отсутствии создаёт её. Найденную/созданную
 * компанию записывает в свойство текущего элемента инфоблока (по коду свойства)
 * и возвращает её ID и название для дальнейших шагов бизнес-процесса.
 */
class CBPGetCompanyByInnActivity extends BaseActivity
{
    /** @var string[] обязательные модули */
    protected static $requiredModules = ['crm'];

    /** @var string UF-поле компании, в котором хранится ИНН */
    private const COMPANY_INN_FIELD = 'UF_COMPANY_INN';

    /** @var string токен DADATA по умолчанию (заменить на свой) */
    private const DADATA_TOKEN = '56512809ef9c58d8c5cd7e453bb1f11ec4044e5a';

    /** @var string секрет DADATA по умолчанию (заменить на свой) */
    private const DADATA_SECRET = '2a47cf7f1f9fe36365b75ee39291e594ed27e973';

    /**
     * @see parent::__construct()
     * @param string $name имя активити
     */
    public function __construct($name)
    {
        parent::__construct($name);

        $this->arProperties = [
            'Inn' => '',
            'CustomerProperty' => '',
            'ResponsibleId' => 1,

            'CompanyId' => null,
            'CompanyName' => null,
        ];

        $this->SetPropertiesTypes([
            'CompanyId' => ['Type' => FieldType::INT],
            'CompanyName' => ['Type' => FieldType::STRING],
        ]);
    }

    /**
     * Путь к файлу активити.
     *
     * @return string
     */
    protected static function getFileName(): string
    {
        return __FILE__;
    }

    /**
     * Основная логика: DADATA -> поиск/создание компании -> запись в элемент.
     *
     * @return ErrorCollection
     */
    protected function internalExecute(): ErrorCollection
    {
        $errors = parent::internalExecute();

        if (!Loader::includeModule('crm'))
        {
            $errors->setError(new \Bitrix\Main\Error('CRM module is not available'));
            return $errors;
        }

        $inn = trim((string) $this->Inn);
        if ($inn === '')
        {
            $errors->setError(new \Bitrix\Main\Error('Inn is empty'));
            return $errors;
        }

        $party = (new Dadata(self::DADATA_TOKEN, self::DADATA_SECRET))->findPartyByInn($inn);
        $companyName = $party['shortName'] ?? '';
        if ($companyName === '')
        {
            $companyName = Loc::getMessage('GETCOMPANYBYINN_DEFAULT_NAME', ['#INN#' => $inn]);
        }

        $companyId = $this->findCompanyByInn($inn);
        if ($companyId <= 0)
        {
            $companyId = $this->createCompany($companyName, $inn, $party);
        }

        if ($companyId > 0)
        {
            $this->writeCustomerToElement($companyName);
        }

        $this->preparedProperties['CompanyId'] = $companyId;
        $this->preparedProperties['CompanyName'] = $companyName;
        $this->log(Loc::getMessage('GETCOMPANYBYINN_LOG', [
            '#ID#' => $companyId,
            '#NAME#' => $companyName,
        ]));

        return $errors;
    }

    /**
     * Ищет компанию CRM по ИНН в поле UF_COMPANY_INN.
     *
     * @param string $inn ИНН
     * @return int ID компании или 0
     */
    private function findCompanyByInn(string $inn): int
    {
        $rows = \CCrmCompany::GetListEx(
            [],
            ['=' . self::COMPANY_INN_FIELD => $inn, 'CHECK_PERMISSIONS' => 'N'],
            false,
            ['nTopCount' => 1],
            ['ID']
        );
        if ($rows && ($row = $rows->Fetch()))
        {
            return (int) $row['ID'];
        }

        return 0;
    }

    /**
     * Создаёт компанию CRM с данными из DADATA.
     *
     * @param string $name название компании
     * @param string $inn ИНН
     * @param array|null $party данные DADATA
     * @return int ID созданной компании или 0
     */
    private function createCompany(string $name, string $inn, ?array $party): int
    {
        $fields = [
            'TITLE' => $name,
            'COMPANY_TYPE' => 'CUSTOMER',
            'OPENED' => 'Y',
            'ASSIGNED_BY_ID' => (int) $this->ResponsibleId ?: 1,
            self::COMPANY_INN_FIELD => $inn,
        ];

        if (!empty($party['address']))
        {
            $fields['ADDRESS_LEGAL'] = $party['address'];
        }

        $company = new \CCrmCompany(false);
        $id = $company->Add($fields, true, ['DISABLE_USER_FIELD_CHECK' => true]);

        return (int) $id;
    }

    /**
     * Записывает название компании в свойство текущего элемента инфоблока.
     *
     * @param string $companyName название компании
     * @return void
     */
    private function writeCustomerToElement(string $companyName): void
    {
        $property = trim((string) $this->CustomerProperty);
        if ($property === '')
        {
            return;
        }

        $documentId = $this->getRootActivity()->getDocumentId();
        $elementId = (int) ($documentId[2] ?? 0);
        if ($elementId <= 0)
        {
            return;
        }

        $element = \CIBlockElement::GetByID($elementId)->Fetch();
        if (!$element)
        {
            return;
        }

        \CIBlockElement::SetPropertyValuesEx(
            $elementId,
            (int) $element['IBLOCK_ID'],
            [$property => $companyName]
        );
    }

    /**
     * Карта полей диалога настроек активити.
     *
     * @param PropertiesDialog|null $dialog
     * @return array[]
     */
    public static function getPropertiesDialogMap(?PropertiesDialog $dialog = null): array
    {
        return [
            'Inn' => [
                'Name' => Loc::getMessage('GETCOMPANYBYINN_FIELD_INN'),
                'FieldName' => 'inn',
                'Type' => FieldType::STRING,
                'Required' => true,
            ],
            'CustomerProperty' => [
                'Name' => Loc::getMessage('GETCOMPANYBYINN_FIELD_CUSTOMER_PROPERTY'),
                'FieldName' => 'customer_property',
                'Type' => FieldType::STRING,
                'Required' => false,
            ],
            'ResponsibleId' => [
                'Name' => Loc::getMessage('GETCOMPANYBYINN_FIELD_RESPONSIBLE'),
                'FieldName' => 'responsible_id',
                'Type' => FieldType::USER,
                'Required' => false,
            ],
        ];
    }
}
