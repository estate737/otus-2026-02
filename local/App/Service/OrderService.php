<?php

namespace App\Service;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Сервис заказ-нарядов: название и состав запчастей.
 *
 * Запчасти хранятся в товарных позициях сделки (каталог товаров Битрикс24),
 * поэтому менеджер выбирает их из справочника, а не вводит текстом.
 *
 * @package App\Service
 */
class OrderService
{
    /** @var string тип владельца товарных позиций для сделки */
    private const PRODUCT_OWNER_DEAL = 'D';

    /**
     * Возвращает названия запчастей, добавленных в заказ-наряд.
     *
     * @param int $dealId идентификатор сделки
     * @return string[] названия запчастей с количеством
     */
    public function getParts(int $dealId): array
    {
        if (!Loader::includeModule('crm') || $dealId <= 0) {
            return [];
        }

        $rows = \CCrmProductRow::LoadRows(self::PRODUCT_OWNER_DEAL, $dealId);
        $parts = [];
        foreach ((array) $rows as $row) {
            $quantity = (float) ($row['QUANTITY'] ?? 0);
            $name = (string) ($row['PRODUCT_NAME'] ?? '');
            if ($name === '') {
                continue;
            }

            $parts[] = $quantity > 1
                ? $name . ' x ' . (int) $quantity
                : $name;
        }

        return $parts;
    }

    /**
     * Записывает запчасти в заказ-наряд как товарные позиции.
     *
     * @param int $dealId идентификатор сделки
     * @param array<int, array{ID: int, NAME: string, PRICE: float, QUANTITY: int}> $parts запчасти
     * @return bool успешность записи
     */
    public function setParts(int $dealId, array $parts): bool
    {
        if (!Loader::includeModule('crm') || $dealId <= 0) {
            return false;
        }

        $rows = [];
        foreach ($parts as $part) {
            $rows[] = [
                'PRODUCT_ID' => (int) $part['ID'],
                'PRODUCT_NAME' => (string) $part['NAME'],
                'PRICE' => (float) $part['PRICE'],
                'QUANTITY' => (int) ($part['QUANTITY'] ?? 1),
                'CURRENCY_ID' => 'RUB',
            ];
        }

        return (bool) \CCrmProductRow::SaveRows(self::PRODUCT_OWNER_DEAL, $dealId, $rows);
    }

    /**
     * Формирует название заказ-наряда по клиенту и автомобилю.
     *
     * Формат: «Фамилия Имя, Марка Модель, Госномер».
     *
     * @param int $contactId идентификатор клиента
     * @param int $carId идентификатор автомобиля
     * @return string название либо пустая строка, если данных нет
     */
    public function buildTitle(int $contactId, int $carId): string
    {
        $garage = new GarageService();
        $car = $garage->getCar($carId);
        if ($car === null) {
            return '';
        }

        if ($contactId <= 0) {
            $contactId = (int) $car['CONTACT_ID'];
        }

        $clientName = $this->getContactName($contactId);
        $parts = array_filter([
            $clientName,
            $car['TITLE'],
            $car['NUMBER'],
        ]);

        return implode(', ', $parts);
    }

    /**
     * Возвращает имя клиента.
     *
     * @param int $contactId идентификатор контакта
     * @return string
     */
    private function getContactName(int $contactId): string
    {
        if ($contactId <= 0 || !Loader::includeModule('crm')) {
            return '';
        }

        $row = \CCrmContact::GetListEx(
            [],
            ['=ID' => $contactId, 'CHECK_PERMISSIONS' => 'N'],
            false,
            false,
            ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME']
        )->Fetch();

        if (!$row) {
            return '';
        }

        return trim($row['LAST_NAME'] . ' ' . $row['NAME'] . ' ' . $row['SECOND_NAME']);
    }
}
