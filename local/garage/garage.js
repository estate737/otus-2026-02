/**
 * Гараж клиента: открытие всплывающего окна с историей обслуживания автомобиля.
 */
(function () {
    'use strict';

    if (window.ServiceGarage) {
        return;
    }

    var ServiceGarage = {
        popup: null,

        /**
         * Навешивает обработчики на карточки автомобилей.
         */
        init: function () {
            document.addEventListener('click', function (event) {
                var card = event.target.closest ? event.target.closest('.garage-card') : null;
                if (!card) {
                    return;
                }
                ServiceGarage.openHistory(card.getAttribute('data-car-id'));
            });
        },

        /**
         * Загружает историю по автомобилю и показывает всплывающее окно.
         *
         * @param {string} carId идентификатор автомобиля
         */
        openHistory: function (carId) {
            BX.ajax({
                url: '/local/garage/history.php?carId=' + encodeURIComponent(carId),
                method: 'GET',
                dataType: 'json',
                onsuccess: function (data) {
                    if (!data || data.error) {
                        ServiceGarage.show('Гараж', '<div class="garage-popup-empty">' + ((data && data.error) || 'Ошибка загрузки') + '</div>');
                        return;
                    }
                    ServiceGarage.show(
                        data.car.title + ' - ' + data.car.number + ' (' + data.car.contactName + ')',
                        ServiceGarage.renderHistory(data.deals)
                    );
                },
                onfailure: function () {
                    ServiceGarage.show('Гараж', '<div class="garage-popup-empty">Не удалось получить данные</div>');
                }
            });
        },

        /**
         * Формирует разметку истории обслуживания.
         *
         * @param {Array} deals список заказ-нарядов
         * @returns {string} html
         */
        renderHistory: function (deals) {
            if (!deals || !deals.length) {
                return '<div class="garage-popup-empty">По этому автомобилю обращений пока нет</div>';
            }

            var rows = deals.map(function (deal) {
                var parts = (deal.PARTS && deal.PARTS.length)
                    ? deal.PARTS.map(function (part) {
                        return '<li>' + BX.util.htmlspecialchars(part) + '</li>';
                    }).join('')
                    : '<li class="garage-popup-muted">не указаны</li>';

                var sum = Number(deal.OPPORTUNITY).toLocaleString('ru-RU') + ' ' + (deal.CURRENCY_ID === 'RUB' ? 'руб.' : deal.CURRENCY_ID);

                return '<div class="garage-deal">'
                    + '<div class="garage-deal-head">'
                    + '<a class="garage-deal-title" href="/crm/deal/details/' + deal.ID + '/" target="_blank">'
                    + BX.util.htmlspecialchars(deal.TITLE) + '</a>'
                    + '<span class="garage-deal-stage">' + BX.util.htmlspecialchars(deal.STAGE_NAME) + '</span>'
                    + '</div>'
                    + '<div class="garage-deal-meta">'
                    + '<span>Создан: <b>' + BX.util.htmlspecialchars(deal.DATE_CREATE) + '</b></span>'
                    + '<span>Сумма: <b>' + sum + '</b></span>'
                    + '<span>Ответственный: <a href="/company/personal/user/' + deal.ASSIGNED_BY_ID + '/" target="_blank">'
                    + BX.util.htmlspecialchars(deal.ASSIGNED_BY_NAME) + '</a></span>'
                    + '</div>'
                    + '<div class="garage-deal-parts">Запчасти:<ul>' + parts + '</ul></div>'
                    + '</div>';
            }).join('');

            return '<div class="garage-popup-list">' + rows + '</div>';
        },

        /**
         * Показывает всплывающее окно с заданным заголовком и содержимым.
         *
         * @param {string} title заголовок окна
         * @param {string} content html содержимого
         */
        show: function (title, content) {
            if (this.popup) {
                this.popup.destroy();
            }

            this.popup = new BX.PopupWindow('service-garage-popup', null, {
                content: content,
                titleBar: { content: BX.create('span', { text: title }) },
                closeIcon: true,
                closeByEsc: true,
                overlay: { backgroundColor: '#000', opacity: 40 },
                width: 760,
                maxHeight: 560,
                buttons: [
                    new BX.PopupWindowButtonLink({
                        text: 'Закрыть',
                        events: {
                            click: function () {
                                this.popupWindow.close();
                            }
                        }
                    })
                ]
            });

            this.popup.show();
        }
    };

    window.ServiceGarage = ServiceGarage;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            ServiceGarage.init();
        });
    } else {
        ServiceGarage.init();
    }
})();
