<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Bizproc\Activity\BaseActivity;
use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Bizproc\FieldType;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use App\Service\CompanyService;

/**
 * Активити "Заказчик по ИНН".
 *
 * Принимает ИНН, через сервис CompanyService ищет компанию в CRM по реквизитам
 * (RQ_INN) и при отсутствии создаёт её с автоматическим заполнением реквизитов
 * (ИНН, КПП, ОГРН, наименование) из сервиса DADATA. Найденную/созданную
 * компанию записывает в свойство текущего элемента инфоблока (по коду свойства)
 * и возвращает её ID и название для дальнейших шагов бизнес-процесса.
 */
class CBPGetCompanyByInnActivity extends BaseActivity
{
    /** @var string[] обязательные модули */
    protected static $requiredModules = ['crm'];

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
     * Основная логика: поиск/создание компании через CompanyService, запись в элемент.
     *
     * @return ErrorCollection
     */
    protected function internalExecute(): ErrorCollection
    {
        $errors = parent::internalExecute();

        if (!Loader::includeModule('crm'))
        {
            $errors->setError(new Error(Loc::getMessage('GETCOMPANYBYINN_ERROR_NO_CRM')));
            return $errors;
        }

        $inn = trim((string) $this->Inn);
        if ($inn === '')
        {
            $errors->setError(new Error(Loc::getMessage('GETCOMPANYBYINN_ERROR_EMPTY_INN')));
            return $errors;
        }

        try
        {
            $service = new CompanyService(self::DADATA_TOKEN, self::DADATA_SECRET);
            $company = $service->getOrCreateByInn($inn, (int) $this->ResponsibleId ?: 1);
        }
        catch (Throwable $e)
        {
            $errors->setError(new Error($e->getMessage()));
            return $errors;
        }

        $companyId = (int) $company['ID'];
        $companyName = (string) $company['TITLE'];

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
