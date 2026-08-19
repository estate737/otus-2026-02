<?php

namespace App\Service;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\HttpClient;

Loc::loadMessages(__FILE__);

/**
 * Сервис складских остатков запчастей.
 *
 * Данные об остатках приходят из внешнего сервиса: он возвращает текущее
 * количество запчасти на складах. Если запчасть закончилась, автоматически
 * создаётся заявка на закупку, склад пополняется, закупщик получает
 * уведомление, а заявка сразу закрывается как выполненная.
 *
 * @package App\Service
 */
class StockService
{
    /** @var string адрес внешнего сервиса складских остатков */
    private const EXTERNAL_SERVICE_URL = 'https://www.random.org/integers/?num=1&min=0&max=10&col=1&base=10&format=plain&rnd=new';

    /** @var int таймаут обращения к внешнему сервису, секунд */
    private const REQUEST_TIMEOUT = 10;

    /** @var string файл журнала обмена с внешним сервисом */
    private const LOG_FILE = '/local/logs/stock_sync.log';

    /** @var string символьный код раздела запчастей в каталоге */
    public const PARTS_SECTION_CODE = 'spare_parts';

    /**
     * Синхронизирует остатки всех запчастей с внешним сервисом.
     *
     * @return array{checked: int, restocked: int} итог синхронизации
     */
    public function syncAll(): array
    {
        $checked = 0;
        $restocked = 0;

        foreach ($this->getParts() as $part)
        {
            $checked++;
            $quantity = $this->fetchExternalQuantity();

            if ($quantity === null)
            {
                $this->log(Loc::getMessage('SERVICE_STOCK_LOG_NO_ANSWER', ['#PART#' => $part['NAME']]));
                continue;
            }

            $this->setQuantity((int) $part['ID'], $quantity);
            $this->log(Loc::getMessage('SERVICE_STOCK_LOG_UPDATED', [
                '#PART#' => $part['NAME'],
                '#QUANTITY#' => $quantity,
            ]));

            if ($quantity === 0)
            {
                $this->handleOutOfStock((int) $part['ID'], (string) $part['NAME']);
                $restocked++;
            }
        }

        return ['checked' => $checked, 'restocked' => $restocked];
    }

    /**
     * Обрабатывает нулевой остаток: заявка, пополнение, уведомление.
     *
     * @param int $productId идентификатор товара
     * @param string $productName название запчасти
     * @return void
     */
    public function handleOutOfStock(int $productId, string $productName): void
    {
        $purchase = new PurchaseService();
        $approver = $purchase->getApprover();
        $quantity = PurchaseService::AUTO_PURCHASE_QUANTITY;

        $requestId = $purchase->createRequest($productId, $productName, $quantity, $approver, true);

        if ($requestId > 0)
        {
            // одобрение пополняет склад с нуля до нужного количества и закрывает заявку
            $purchase->approve($requestId, $approver, false);
        }
        else
        {
            $this->setQuantity($productId, $quantity);
        }

        $purchase->notify($approver, Loc::getMessage('SERVICE_STOCK_NOTIFY_AUTO', [
            '#PART#' => $productName,
            '#QUANTITY#' => $quantity,
        ]));

        $this->log(Loc::getMessage('SERVICE_STOCK_LOG_AUTO', [
            '#PART#' => $productName,
            '#QUANTITY#' => $quantity,
            '#REQUEST#' => $requestId,
        ]));
    }

    /**
     * Запрашивает текущее количество во внешнем сервисе.
     *
     * @return int|null количество либо null, если сервис недоступен
     */
    public function fetchExternalQuantity(): ?int
    {
        $client = new HttpClient(['socketTimeout' => self::REQUEST_TIMEOUT, 'streamTimeout' => self::REQUEST_TIMEOUT]);
        $response = $client->get(self::EXTERNAL_SERVICE_URL);

        if ($response === false || $client->getStatus() !== 200)
        {
            return null;
        }

        $value = trim((string) $response);

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * Возвращает список запчастей каталога.
     *
     * @return array<int, array{ID: int, NAME: string}>
     */
    public function getParts(): array
    {
        if (!Loader::includeModule('iblock') || !Loader::includeModule('catalog'))
        {
            return [];
        }

        $catalogId = $this->getCatalogId();
        if ($catalogId <= 0)
        {
            return [];
        }

        // запчасти живут в собственном разделе каталога, чтобы не задевать прочие товары
        $filter = ['IBLOCK_ID' => $catalogId, 'ACTIVE' => 'Y'];
        $sectionId = $this->getPartsSectionId($catalogId);
        if ($sectionId > 0)
        {
            $filter['SECTION_ID'] = $sectionId;
        }

        $parts = [];
        $res = \CIBlockElement::GetList(
            ['NAME' => 'ASC'],
            $filter,
            false,
            false,
            ['ID', 'NAME']
        );
        while ($row = $res->Fetch())
        {
            $parts[] = ['ID' => (int) $row['ID'], 'NAME' => (string) $row['NAME']];
        }

        return $parts;
    }

    /**
     * Возвращает идентификатор раздела «Запчасти» в каталоге.
     *
     * @param int $catalogId идентификатор инфоблока каталога
     * @return int идентификатор раздела либо 0, если раздел не создан
     */
    public function getPartsSectionId(int $catalogId): int
    {
        static $cache = [];
        if (isset($cache[$catalogId]))
        {
            return $cache[$catalogId];
        }

        $row = \CIBlockSection::GetList(
            [],
            ['IBLOCK_ID' => $catalogId, '=CODE' => self::PARTS_SECTION_CODE],
            false,
            ['ID']
        )->Fetch();

        $cache[$catalogId] = $row ? (int) $row['ID'] : 0;

        return $cache[$catalogId];
    }

    /**
     * Возвращает текущий остаток запчасти.
     *
     * @param int $productId идентификатор товара
     * @return int
     */
    public function getQuantity(int $productId): int
    {
        if (!Loader::includeModule('catalog'))
        {
            return 0;
        }

        $row = \Bitrix\Catalog\ProductTable::getList([
            'filter' => ['=ID' => $productId],
            'select' => ['ID', 'QUANTITY'],
            'limit' => 1,
        ])->fetch();

        return $row ? (int) $row['QUANTITY'] : 0;
    }

    /**
     * Устанавливает остаток запчасти.
     *
     * @param int $productId идентификатор товара
     * @param int $quantity новое количество
     * @return void
     */
    public function setQuantity(int $productId, int $quantity): void
    {
        if (Loader::includeModule('catalog'))
        {
            \CCatalogProduct::Update($productId, ['QUANTITY' => $quantity]);
        }
    }

    /**
     * Увеличивает остаток запчасти.
     *
     * @param int $productId идентификатор товара
     * @param int $quantity добавляемое количество
     * @return void
     */
    public function increaseQuantity(int $productId, int $quantity): void
    {
        $this->setQuantity($productId, $this->getQuantity($productId) + $quantity);
    }

    /**
     * Возвращает идентификатор каталога товаров CRM.
     *
     * @return int
     */
    private function getCatalogId(): int
    {
        $catalogId = (int) Option::get('crm', 'default_product_catalog_id', 0);
        if ($catalogId <= 0 && Loader::includeModule('crm'))
        {
            $row = \CCrmCatalog::GetList([], [])->Fetch();
            $catalogId = (int) ($row['ID'] ?? 0);
        }

        return $catalogId;
    }

    /**
     * Пишет строку в журнал обмена.
     *
     * @param string $message сообщение
     * @return void
     */
    private function log(string $message): void
    {
        $line = date('c') . ' ' . $message . PHP_EOL;
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . self::LOG_FILE, $line, FILE_APPEND);
    }

    /**
     * Агент ежедневной синхронизации остатков.
     *
     * @return string выражение перезапуска агента
     */
    public static function syncAgent(): string
    {
        (new self())->syncAll();

        return '\App\Service\StockService::syncAgent();';
    }
}
