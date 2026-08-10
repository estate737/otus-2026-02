<?php
/**
 * Содержимое вкладки «Гараж» в карточке контакта: список автомобилей клиента.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use App\Service\GarageService;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

Loc::loadMessages(__FILE__);

$contactId = (int) ($_GET['contactId'] ?? 0);
$cars = (new GarageService())->getCarsByContact($contactId);

Extension::load(['popup', 'ajax']);
\CJSCore::Init(['popup']);

$asset = \Bitrix\Main\Page\Asset::getInstance();
$asset->addCss('/local/garage/garage.css');
$asset->addJs('/local/garage/garage.js');
?>
<div class="garage-wrap">
    <?php if (empty($cars)): ?>
        <div class="garage-empty"><?= Loc::getMessage('SERVICE_GARAGE_EMPTY') ?></div>
    <?php else: ?>
        <div class="garage-grid">
            <?php foreach ($cars as $car): ?>
                <div class="garage-card" data-car-id="<?= (int) $car['ID'] ?>" data-contact-id="<?= $contactId ?>">
                    <div class="garage-card-title"><?= htmlspecialcharsbx($car['TITLE']) ?></div>
                    <div class="garage-card-number"><?= htmlspecialcharsbx($car['NUMBER']) ?></div>
                    <div class="garage-card-props">
                        <span><?= Loc::getMessage('SERVICE_GARAGE_YEAR') ?>: <b><?= (int) $car['YEAR'] ?></b></span>
                        <span><?= Loc::getMessage('SERVICE_GARAGE_COLOR') ?>: <b><?= htmlspecialcharsbx($car['COLOR']) ?></b></span>
                        <span><?= Loc::getMessage('SERVICE_GARAGE_MILEAGE') ?>: <b><?= number_format($car['MILEAGE'], 0, '.', ' ') ?></b></span>
                    </div>
                    <div class="garage-card-hint"><?= Loc::getMessage('SERVICE_GARAGE_HINT') ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
