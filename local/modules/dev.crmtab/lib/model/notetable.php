<?php

namespace Dev\Crmtab\Model;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

Loc::loadMessages(__FILE__);

/**
 * ORM модель заметок, привязанных к CRM сущностям
 *
 * @package Dev\Crmtab\Model
 */
class NoteTable extends DataManager
{
    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'crmtab_note';
    }

    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary(true)
                ->configureAutocomplete(true)
                ->configureTitle(Loc::getMessage('CRMTAB_FIELD_ID')),

            (new IntegerField('ENTITY_TYPE_ID'))
                ->configureRequired(true)
                ->configureTitle(Loc::getMessage('CRMTAB_FIELD_ENTITY_TYPE_ID')),

            (new IntegerField('ENTITY_ID'))
                ->configureRequired(true)
                ->configureTitle(Loc::getMessage('CRMTAB_FIELD_ENTITY_ID')),

            (new StringField('TITLE'))
                ->configureRequired(true)
                ->addValidator(new LengthValidator(1, 255))
                ->configureTitle(Loc::getMessage('CRMTAB_FIELD_TITLE')),

            (new TextField('BODY'))
                ->configureTitle(Loc::getMessage('CRMTAB_FIELD_BODY')),

            (new StringField('AUTHOR'))
                ->addValidator(new LengthValidator(0, 100))
                ->configureTitle(Loc::getMessage('CRMTAB_FIELD_AUTHOR')),

            (new DatetimeField('CREATED_AT'))
                ->configureTitle(Loc::getMessage('CRMTAB_FIELD_CREATED_AT')),
        ];
    }
}
