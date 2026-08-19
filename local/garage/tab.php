<?php
/**
 * Содержимое вкладки «Гараж» в карточке контакта: список автомобилей клиента.
 *
 * Стили и скрипт выводятся внутри фрагмента: вкладка подгружается через AJAX,
 * поэтому подключение через Asset до пользователя не доходит.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use App\Service\GarageService;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$contactId = (int) ($_GET['contactId'] ?? 0);
$garage = new GarageService();
$cars = $garage->getCarsByContact($contactId);
$categoryId = (int) \Bitrix\Main\Config\Option::get('main', '~service_center_category', 1);

\CJSCore::Init(['popup', 'ajax']);
?>
<style>
    .garage-wrap { padding: 16px; }
    .garage-empty { color: #828b95; padding: 24px; text-align: center; }
    .garage-grid { display: flex; flex-wrap: wrap; gap: 12px; }
    .garage-card {
        width: 250px; padding: 14px 16px; border: 1px solid #dfe0e3; border-radius: 8px;
        background: #fff; cursor: pointer; transition: box-shadow .15s, border-color .15s;
    }
    .garage-card:hover { border-color: #2fc7f7; box-shadow: 0 2px 10px rgba(0,0,0,.08); }
    .garage-card-title { font-size: 15px; font-weight: 600; color: #333; margin-bottom: 6px; }
    .garage-card-number {
        display: inline-block; padding: 2px 8px; border: 1px solid #b9bec4; border-radius: 4px;
        font-size: 13px; letter-spacing: .5px; margin-bottom: 10px; background: #f7f8f9;
    }
    .garage-card-props { display: flex; flex-direction: column; gap: 3px; font-size: 12px; color: #6a737c; }
    .garage-card-hint { margin-top: 10px; font-size: 11px; color: #2fc7f7; }
    .garage-card-actions { margin-top: 12px; display: flex; gap: 8px; align-items: center; }
    .garage-btn {
        display: inline-block; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600;
        text-decoration: none; cursor: pointer; border: 1px solid transparent; line-height: 1;
    }
    .garage-btn-primary { background: #3bc8f5; color: #fff; }
    .garage-btn-primary:hover { background: #2fb8e5; }
    .garage-btn-secondary { background: #fff; color: #535c69; border-color: #c6cdd3; }
    .garage-btn-secondary:hover { border-color: #3bc8f5; color: #3bc8f5; }
    .garage-status { font-size: 11px; padding: 2px 8px; border-radius: 10px; }
    .garage-status-busy { background: #fff1d6; color: #b57500; }
    .garage-status-free { background: #e8f8ee; color: #1f8f4a; }
    .garage-popup-list { padding: 8px 4px; max-height: 460px; overflow-y: auto; }
    .garage-popup-empty { padding: 24px; text-align: center; color: #828b95; }
    .garage-deal { padding: 12px 14px; border: 1px solid #edeef0; border-radius: 6px; margin-bottom: 10px; }
    .garage-deal-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 6px; }
    .garage-deal-title { font-weight: 600; color: #2067b0; text-decoration: none; }
    .garage-deal-stage { font-size: 12px; padding: 2px 8px; border-radius: 10px; background: #eef6fc; color: #2067b0; white-space: nowrap; }
    .garage-deal-meta { display: flex; flex-wrap: wrap; gap: 16px; font-size: 12px; color: #6a737c; margin-bottom: 6px; }
    .garage-deal-parts { font-size: 12px; color: #6a737c; }
    .garage-deal-parts ul { margin: 4px 0 0 18px; }
</style>

<div class="garage-wrap">
    <?php if (empty($cars)): ?>
        <div class="garage-empty"><?= Loc::getMessage('SERVICE_GARAGE_EMPTY') ?></div>
    <?php else: ?>
        <div class="garage-grid">
            <?php foreach ($cars as $car): ?>
                <?php
                $openDeals = $garage->getOpenDeals((int) $car['ID']);
                $openDeal = $openDeals[0] ?? null;
                $createUrl = '/crm/deal/details/0/?category_id=' . $categoryId
                    . '&contact_id=' . $contactId
                    . '&uf_crm_deal_car=' . (int) $car['ID'];
                ?>
                <div class="garage-card" data-garage-car="<?= (int) $car['ID'] ?>">
                    <div class="garage-card-title"><?= htmlspecialcharsbx($car['TITLE']) ?></div>
                    <div class="garage-card-number"><?= htmlspecialcharsbx($car['NUMBER']) ?></div>
                    <div class="garage-card-props">
                        <span><?= Loc::getMessage('SERVICE_GARAGE_YEAR') ?>: <b><?= (int) $car['YEAR'] ?></b></span>
                        <span><?= Loc::getMessage('SERVICE_GARAGE_COLOR') ?>: <b><?= htmlspecialcharsbx($car['COLOR']) ?></b></span>
                        <span><?= Loc::getMessage('SERVICE_GARAGE_MILEAGE') ?>: <b><?= number_format($car['MILEAGE'], 0, '.', ' ') ?></b></span>
                    </div>
                    <div class="garage-card-actions" onclick="event.stopPropagation();">
                        <?php if ($openDeal): ?>
                            <a class="garage-btn garage-btn-secondary" href="/crm/deal/details/<?= (int) $openDeal['ID'] ?>/" target="_top">
                                <?= Loc::getMessage('SERVICE_GARAGE_OPEN_ORDER') ?> №<?= (int) $openDeal['ID'] ?>
                            </a>
                            <span class="garage-status garage-status-busy"><?= htmlspecialcharsbx($openDeal['STAGE_NAME']) ?></span>
                        <?php else: ?>
                            <a class="garage-btn garage-btn-primary" href="<?= htmlspecialcharsbx($createUrl) ?>" target="_top">
                                <?= Loc::getMessage('SERVICE_GARAGE_CREATE_ORDER') ?>
                            </a>
                            <span class="garage-status garage-status-free"><?= Loc::getMessage('SERVICE_GARAGE_FREE') ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="garage-card-hint"><?= Loc::getMessage('SERVICE_GARAGE_HINT') ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    (function () {
        var openHistory = function (carId) {
            BX.ajax({
                url: '/local/garage/history.php?carId=' + encodeURIComponent(carId),
                method: 'GET',
                dataType: 'json',
                onsuccess: function (data) {
                    if (!data || data.error) {
                        show('Гараж', '<div class="garage-popup-empty">' + ((data && data.error) || 'Ошибка загрузки') + '</div>');
                        return;
                    }
                    show(
                        data.car.title + ' - ' + data.car.number + ' (' + data.car.contactName + ')',
                        render(data.deals)
                    );
                },
                onfailure: function () {
                    show('Гараж', '<div class="garage-popup-empty">Не удалось получить данные</div>');
                }
            });
        };

        var render = function (deals) {
            if (!deals || !deals.length) {
                return '<div class="garage-popup-empty">По этому автомобилю обращений пока нет</div>';
            }

            return '<div class="garage-popup-list">' + deals.map(function (deal) {
                var parts = (deal.PARTS && deal.PARTS.length)
                    ? deal.PARTS.map(function (part) { return '<li>' + BX.util.htmlspecialchars(part) + '</li>'; }).join('')
                    : '<li>не указаны</li>';
                var sum = Number(deal.OPPORTUNITY).toLocaleString('ru-RU') + ' руб.';

                return '<div class="garage-deal">'
                    + '<div class="garage-deal-head">'
                    + '<a class="garage-deal-title" href="/crm/deal/details/' + deal.ID + '/" target="_blank">' + BX.util.htmlspecialchars(deal.TITLE) + '</a>'
                    + '<span class="garage-deal-stage">' + BX.util.htmlspecialchars(deal.STAGE_NAME) + '</span>'
                    + '</div>'
                    + '<div class="garage-deal-meta">'
                    + '<span>Создан: <b>' + BX.util.htmlspecialchars(deal.DATE_CREATE) + '</b></span>'
                    + '<span>Сумма: <b>' + sum + '</b></span>'
                    + '<span>Ответственный: <a href="/company/personal/user/' + deal.ASSIGNED_BY_ID + '/" target="_blank">' + BX.util.htmlspecialchars(deal.ASSIGNED_BY_NAME) + '</a></span>'
                    + '</div>'
                    + '<div class="garage-deal-parts">Запчасти:<ul>' + parts + '</ul></div>'
                    + '</div>';
            }).join('') + '</div>';
        };

        var show = function (title, content) {
            if (window.serviceGaragePopup) {
                window.serviceGaragePopup.destroy();
            }
            window.serviceGaragePopup = new BX.PopupWindow('service-garage-popup', null, {
                content: content,
                titleBar: { content: BX.create('span', { text: title }) },
                closeIcon: true,
                closeByEsc: true,
                overlay: { backgroundColor: '#000', opacity: 40 },
                width: 780,
                maxHeight: 560,
                buttons: [
                    new BX.PopupWindowButtonLink({
                        text: 'Закрыть',
                        events: { click: function () { this.popupWindow.close(); } }
                    })
                ]
            });
            window.serviceGaragePopup.show();
        };

        if (!window.serviceGarageBound) {
            window.serviceGarageBound = true;
            document.addEventListener('click', function (event) {
                var card = event.target.closest ? event.target.closest('[data-garage-car]') : null;
                if (card) {
                    openHistory(card.getAttribute('data-garage-car'));
                }
            });
        }
        window.serviceGarageOpen = openHistory;
    })();
</script>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
