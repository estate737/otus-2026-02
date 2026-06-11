<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Bizproc\Activity\BaseActivity;
use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Bizproc\FieldType;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

/**
 * Активити "Создать сделку по заявке".
 *
 * Создаёт сделку CRM, привязанную к указанной компании, с названием из вида
 * работ и суммой из заявки. Возвращает ID созданной сделки.
 */
class CBPCreateOrderDealActivity extends BaseActivity
{
    /** @var string[] обязательные модули */
    protected static $requiredModules = ['crm'];

    /**
     * @see parent::__construct()
     * @param string $name имя активити
     */
    public function __construct($name)
    {
        parent::__construct($name);

        $this->arProperties = [
            'CompanyId' => null,
            'DealTitle' => '',
            'Sum' => 0,
            'ResponsibleId' => 1,

            'DealId' => null,
        ];

        $this->SetPropertiesTypes([
            'DealId' => ['Type' => FieldType::INT],
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
     * Создаёт сделку CRM и возвращает её ID.
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

        $title = trim((string) $this->DealTitle);
        if ($title === '')
        {
            $title = Loc::getMessage('CREATEORDERDEAL_DEFAULT_TITLE');
        }

        $fields = [
            'TITLE' => $title,
            'COMPANY_ID' => (int) $this->CompanyId,
            'OPPORTUNITY' => (float) $this->Sum,
            'CURRENCY_ID' => 'RUB',
            'OPENED' => 'Y',
            'ASSIGNED_BY_ID' => (int) $this->ResponsibleId ?: 1,
        ];

        $deal = new \CCrmDeal(false);
        $dealId = (int) $deal->Add($fields, true, ['DISABLE_USER_FIELD_CHECK' => true]);

        $this->preparedProperties['DealId'] = $dealId;
        $this->log(Loc::getMessage('CREATEORDERDEAL_LOG', [
            '#ID#' => $dealId,
            '#TITLE#' => $title,
        ]));

        return $errors;
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
            'CompanyId' => [
                'Name' => Loc::getMessage('CREATEORDERDEAL_FIELD_COMPANY_ID'),
                'FieldName' => 'company_id',
                'Type' => FieldType::INT,
                'Required' => true,
            ],
            'DealTitle' => [
                'Name' => Loc::getMessage('CREATEORDERDEAL_FIELD_TITLE'),
                'FieldName' => 'deal_title',
                'Type' => FieldType::STRING,
                'Required' => false,
            ],
            'Sum' => [
                'Name' => Loc::getMessage('CREATEORDERDEAL_FIELD_SUM'),
                'FieldName' => 'sum',
                'Type' => FieldType::DOUBLE,
                'Required' => false,
            ],
            'ResponsibleId' => [
                'Name' => Loc::getMessage('CREATEORDERDEAL_FIELD_RESPONSIBLE'),
                'FieldName' => 'responsible_id',
                'Type' => FieldType::USER,
                'Required' => false,
            ],
        ];
    }
}
