<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Dev\Crmtab\Model\NoteTable;

Loc::loadMessages(__FILE__);

/**
 * Компонент списка заметок CRM сущности
 */
class CrmTabListComponent extends CBitrixComponent implements Controllerable, Errorable
{
    /** @var ErrorCollection */
    protected $errorCollection;

    public function __construct($component = null)
    {
        parent::__construct($component);
        $this->errorCollection = new ErrorCollection();
    }

    /**
     * Подготовка параметров компонента.
     *
     * @param array $arParams
     * @return array
     */
    public function onPrepareComponentParams($arParams): array
    {
        $arParams['ENTITY_ID'] = (int) ($arParams['ENTITY_ID'] ?? 0);
        $arParams['ENTITY_TYPE_ID'] = (int) ($arParams['ENTITY_TYPE_ID'] ?? 0);

        return $arParams;
    }

    /**
     * Конфигурация AJAX-действий компонента.
     *
     * @return array
     */
    public function configureActions(): array
    {
        return [
            'add' => [
                'prefilters' => [new ActionFilter\Authentication()],
            ],
            'delete' => [
                'prefilters' => [new ActionFilter\Authentication()],
            ],
        ];
    }

    /**
     * Получение заметок по сущности CRM.
     *
     * @return array
     */
    protected function getNotes(): array
    {
        return NoteTable::getList([
            'select' => ['ID', 'TITLE', 'BODY', 'AUTHOR', 'CREATED_AT'],
            'filter' => [
                '=ENTITY_TYPE_ID' => $this->arParams['ENTITY_TYPE_ID'],
                '=ENTITY_ID' => $this->arParams['ENTITY_ID'],
            ],
            'order' => ['CREATED_AT' => 'DESC'],
        ])->fetchAll();
    }

    /**
     * AJAX-действие добавления заметки.
     *
     * @param int $entityTypeId
     * @param int $entityId
     * @param string $title
     * @param string $body
     * @return array|null
     */
    public function addAction(int $entityTypeId, int $entityId, string $title, string $body = ''): ?array
    {
        Loader::includeModule('dev.crmtab');

        $title = trim($title);
        if ($title === '' || $entityId <= 0 || $entityTypeId <= 0)
        {
            $this->errorCollection->setError(new \Bitrix\Main\Error(Loc::getMessage('CRMTAB_LIST_VALIDATION_ERROR')));
            return null;
        }

        global $USER;
        $author = '';
        if (is_object($USER) && $USER->IsAuthorized())
        {
            $author = trim($USER->GetFullName() ?: $USER->GetLogin());
        }

        $result = NoteTable::add([
            'ENTITY_TYPE_ID' => $entityTypeId,
            'ENTITY_ID' => $entityId,
            'TITLE' => $title,
            'BODY' => trim($body),
            'AUTHOR' => $author,
            'CREATED_AT' => new DateTime(),
        ]);

        if (!$result->isSuccess())
        {
            $this->errorCollection->setError(new \Bitrix\Main\Error(implode(', ', $result->getErrorMessages())));
            return null;
        }

        return ['id' => $result->getId()];
    }

    /**
     * AJAX-действие удаления заметки.
     *
     * @param int $id
     * @return array|null
     */
    public function deleteAction(int $id): ?array
    {
        Loader::includeModule('dev.crmtab');

        if ($id <= 0)
        {
            return null;
        }

        $result = NoteTable::delete($id);
        if (!$result->isSuccess())
        {
            $this->errorCollection->setError(new \Bitrix\Main\Error(implode(', ', $result->getErrorMessages())));
            return null;
        }

        return ['id' => $id];
    }

    /**
     * @return \Bitrix\Main\Error[]
     */
    public function getErrors(): array
    {
        return $this->errorCollection->toArray();
    }

    /**
     * @param string $code
     * @return \Bitrix\Main\Error|null
     */
    public function getErrorByCode($code): ?\Bitrix\Main\Error
    {
        return $this->errorCollection->getErrorByCode($code);
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
            if (!Loader::includeModule('dev.crmtab'))
            {
                throw new SystemException(Loc::getMessage('CRMTAB_LIST_MODULE_NOT_INSTALLED'));
            }

            $this->arResult['ENTITY_ID'] = $this->arParams['ENTITY_ID'];
            $this->arResult['ENTITY_TYPE_ID'] = $this->arParams['ENTITY_TYPE_ID'];
            $this->arResult['ENTITY_NAME'] = \Dev\Crmtab\EventHandler::getEntityTypeName(
                (int) $this->arParams['ENTITY_TYPE_ID']
            );
            $this->arResult['NOTES'] = $this->getNotes();
            $this->arResult['COUNT'] = count($this->arResult['NOTES']);

            $this->IncludeComponentTemplate();
        }
        catch (SystemException $e)
        {
            ShowError($e->getMessage());
        }
    }
}
