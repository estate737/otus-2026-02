<?php

namespace Models;

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\ORM\Data\DataManager,
    Bitrix\Main\ORM\Fields\IntegerField,
    Bitrix\Main\ORM\Fields\StringField,
    Bitrix\Main\ORM\Fields\DatetimeField,
    Bitrix\Main\ORM\Fields\Validators\LengthValidator,
    Bitrix\Main\ORM\Fields\Validators\RegExpValidator,
    Bitrix\Main\ORM\Fields\Relations\Reference,
    Bitrix\Main\Entity\Query\Join;

use Bitrix\Iblock\ElementTable;
use Models\Lists\CarsPropertyValuesTable as Cars;
use Models\Lists\SalonsPropertyValuesTable as Salons;

Loc::loadMessages(__FILE__);

/**
 * Class TestDrivesTable
 *
 * @package Models
 */
class TestDrivesTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'test_drives';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return [
            'ID' => (new IntegerField('ID',
                    []
                ))->configureTitle(Loc::getMessage('TEST_DRIVES_ID_FIELD'))
                        ->configurePrimary(true)
                        ->configureAutocomplete(true),

            'CLIENT_NAME' => (new StringField('CLIENT_NAME',
                    [
                        'validation' => [__CLASS__, 'validateClientName']
                    ]
                ))->configureTitle(Loc::getMessage('TEST_DRIVES_CLIENT_NAME_FIELD'))
                        ->configureRequired(true),

            'CLIENT_PHONE' => (new StringField('CLIENT_PHONE',
                    [
                        'validation' => [__CLASS__, 'validateClientPhone']
                    ]
                ))->configureTitle(Loc::getMessage('TEST_DRIVES_CLIENT_PHONE_FIELD'))
                        ->configureRequired(true),

            'DURATION' => (new IntegerField('DURATION',
                    []
                ))->configureTitle(Loc::getMessage('TEST_DRIVES_DURATION_FIELD'))
                        ->configureRequired(true),

            'CAR_ID' => (new IntegerField('CAR_ID',
                    []
                ))->configureTitle(Loc::getMessage('TEST_DRIVES_CAR_ID_FIELD'))
                        ->configureRequired(true),

            'SALON_ID' => (new IntegerField('SALON_ID',
                    []
                ))->configureTitle(Loc::getMessage('TEST_DRIVES_SALON_ID_FIELD'))
                        ->configureRequired(true),

            'DRIVE_DATE' => (new DatetimeField('DRIVE_DATE',
                    []
                ))->configureTitle(Loc::getMessage('TEST_DRIVES_DRIVE_DATE_FIELD')),

            (new Reference('CAR_ELEMENT', ElementTable::class, Join::on('this.CAR_ID', 'ref.ID')))
                ->configureJoinType('left'),

            (new Reference('CAR', Cars::class, Join::on('this.CAR_ID', 'ref.IBLOCK_ELEMENT_ID')))
                ->configureJoinType('left'),

            (new Reference('SALON_ELEMENT', ElementTable::class, Join::on('this.SALON_ID', 'ref.ID')))
                ->configureJoinType('left'),

            (new Reference('SALON', Salons::class, Join::on('this.SALON_ID', 'ref.IBLOCK_ELEMENT_ID')))
                ->configureJoinType('left'),
        ];
    }

    /**
     * Returns validators for CLIENT_NAME field.
     *
     * @return array
     */
    public static function validateClientName()
    {
        return [
            new LengthValidator(3, 100),
        ];
    }

    /**
     * Returns validators for CLIENT_PHONE field.
     *
     * @return array
     */
    public static function validateClientPhone()
    {
        return [
            new LengthValidator(5, 20),
            new RegExpValidator('/^[\d\s\+\-\(\)]+$/'),
        ];
    }
}
