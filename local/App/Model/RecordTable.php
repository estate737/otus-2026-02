<?php

namespace App\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\Type\DateTime;

/**
 * Кастомная сущность «Запись» для демонстрации собственных REST-методов.
 *
 * Таблица b_dev_record: ID, название, описание, дата создания.
 *
 * @package App\Model
 */
class RecordTable extends DataManager
{
    /**
     * Возвращает имя таблицы сущности.
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return 'b_dev_record';
    }

    /**
     * Возвращает описание полей сущности.
     *
     * @return array
     */
    public static function getMap(): array
    {
        return [
            new IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
            ]),
            new StringField('TITLE', [
                'required' => true,
                'validation' => static fn() => [new LengthValidator(1, 255)],
            ]),
            new TextField('DESCRIPTION'),
            new DatetimeField('CREATED_AT', [
                'default' => static fn() => new DateTime(),
            ]),
        ];
    }
}
