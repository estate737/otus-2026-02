/**
 * ДЗ #8. Подмена штатного окна "Начать рабочий день" / "Продолжить" собственным
 * модальным окном на BX.PopupWindowManager.
 *
 * Логика:
 *   1. При инициализации запрашиваем статус таймера у /homeworks/homework8/status.php.
 *   2. Подписываемся на событие onTimeManWindowOpen (старый модуль) + ловим click capture
 *      по кнопке таймера в правом верхнем углу (новый Vue UI Битрикс24).
 *   3. Перехватываем клик ТОЛЬКО если день не идёт (EMPTY/CLOSED/PAUSED).
 *      Если день идёт (OPENED), пропускаем штатное поведение - там пауза/закрытие.
 *   4. Показываем кастомный попап с текстом и кнопкой подтверждения.
 *   5. Подтверждение - AJAX:
 *        EMPTY/CLOSED -> /homeworks/homework8/start.php (новый день),
 *        PAUSED       -> /homeworks/homework8/continue.php (relaunch после паузы).
 *      После успеха перезагружаем страницу, чтобы Vue-кнопка обновила состояние.
 *   6. Закрытие попапа (крестик / Esc / клик вне) - начало/продолжение отменяется.
 */
(function () {
	'use strict';

	if (window.OtusTmStart)
	{
		return;
	}

	window.OtusTmStart = {
		popup: null,
		bypass: false,
		lastButton: null,
		tmWindow: null,
		tmStatus: 'EMPTY',

		init: function () {
			if (typeof BX === 'undefined')
			{
				return;
			}

			OtusTmStart.refreshStatus();

			if (typeof BX.addCustomEvent === 'function')
			{
				BX.addCustomEvent('onTimeManWindowOpen', OtusTmStart.onLegacyOpen);
			}

			document.addEventListener('click', OtusTmStart.onAnyClick, true);
			console.log('[OtusTmStart] init: listening for timeman button clicks');
		},

		refreshStatus: function () {
			BX.ajax({
				url: '/homeworks/homework8/status.php',
				method: 'GET',
				dataType: 'json',
				onsuccess: function (response) {
					if (response && response.status)
					{
						OtusTmStart.tmStatus = response.status;
						console.log('[OtusTmStart] timeman status:', OtusTmStart.tmStatus);
					}
				}
			});
		},

		/**
		 * Старая схема: модуль timeman выбрасывает onTimeManWindowOpen
		 * с объектом CTimeManWindow.
		 */
		onLegacyOpen: function (tmWindow) {
			if (OtusTmStart.bypass)
			{
				OtusTmStart.bypass = false;
				return;
			}

			if (OtusTmStart.tmStatus === 'OPENED')
			{
				return;
			}

			OtusTmStart.tmWindow = tmWindow;
			setTimeout(function () {
				if (tmWindow && typeof tmWindow.Close === 'function')
				{
					tmWindow.Close();
				}
				OtusTmStart.showConfirm();
			}, 0);
		},

		/**
		 * Новая схема: ловим click capture по любому tm-элементу.
		 * Если день уже идёт - не перехватываем (там пауза/закрытие).
		 */
		onAnyClick: function (e) {
			if (OtusTmStart.bypass)
			{
				return;
			}

			if (OtusTmStart.tmStatus === 'OPENED')
			{
				return;
			}

			var btn = OtusTmStart.findTmButton(e.target);
			if (!btn)
			{
				return;
			}

			console.log('[OtusTmStart] intercepted click, status=' + OtusTmStart.tmStatus, btn);
			e.stopImmediatePropagation();
			e.preventDefault();

			OtusTmStart.lastButton = btn;
			OtusTmStart.showConfirm();
		},

		/**
		 * Поднимаемся по дереву от target и ищем элемент-кнопку таймера.
		 * Критерий: id или class содержит подстроку, относящуюся к timeman.
		 */
		findTmButton: function (target) {
			var el = target;
			while (el && el !== document.body && el !== document)
			{
				var id = (el.id || '').toLowerCase();
				var cls = typeof el.className === 'string' ? el.className.toLowerCase() : '';
				var bag = id + ' ' + cls;

				if (
					bag.indexOf('timeman') > -1
					|| bag.indexOf('tm-button') > -1
					|| bag.indexOf('bx-tm') > -1
					|| bag.indexOf('bxtm') > -1
					|| /(^|\s)tm-/.test(cls)
					|| id === 'tmstatus'
				)
				{
					return el;
				}
				el = el.parentElement;
			}
			return null;
		},

		showConfirm: function () {
			var isPaused = OtusTmStart.tmStatus === 'PAUSED';
			var title = isPaused ? 'Продолжить рабочий день?' : 'Начать рабочий день?';
			var lead = isPaused
				? 'Рабочий день стоит на паузе. Продолжить учёт времени?'
				: 'Вы собираетесь <b>начать рабочий день</b>. После подтверждения учёт рабочего времени будет запущен.';
			var btnText = isPaused ? 'Продолжить' : 'Начать рабочий день';

			var content =
				'<div style="padding:15px 5px; line-height:1.55; font-size:14px;">' +
					lead + '<br><br>' +
					'<span style="color:#828b95;">Закройте окно, если передумали - ничего не произойдёт.</span>' +
				'</div>';

			OtusTmStart.popup = BX.PopupWindowManager.create('otus-tm-start-popup', null, {
				titleBar: title,
				content: content,
				width: 440,
				closeIcon: true,
				overlay: true,
				draggable: { restrict: true },
				buttons: [
					new BX.PopupWindowButton({
						text: btnText,
						className: 'ui-btn ui-btn-success',
						events: {
							click: function () {
								OtusTmStart.confirm();
							}
						}
					}),
					new BX.PopupWindowButtonLink({
						text: 'Отмена',
						className: 'ui-btn ui-btn-link',
						events: {
							click: function () {
								OtusTmStart.popup.close();
							}
						}
					})
				]
			});
			OtusTmStart.popup.show();
		},

		/**
		 * Подтверждение: реально стартуем (или продолжаем) рабочий день
		 * через серверный AJAX, использующий штатные UseCase'ы модуля timeman.
		 * Страницу НЕ перезагружаем: штатная Vue-кнопка обновится через
		 * собственный PushPull-канал модуля. Обновляем только наш локальный
		 * статус, чтобы следующий клик по кнопке (например, "Пауза") уже
		 * проходил без перехвата.
		 */
		confirm: function () {
			if (OtusTmStart.popup)
			{
				OtusTmStart.popup.close();
			}

			var url = OtusTmStart.tmStatus === 'PAUSED'
				? '/homeworks/homework8/continue.php'
				: '/homeworks/homework8/start.php';

			BX.ajax({
				url: url,
				method: 'POST',
				dataType: 'json',
				data: { sessid: BX.bitrix_sessid() },
				onsuccess: function (response) {
					if (response && response.status === 'ok')
					{
						OtusTmStart.tmStatus = 'OPENED';
						console.log('[OtusTmStart] workday started/continued, status=OPENED');
					}
					else
					{
						alert('Не удалось: ' + (response && response.message ? response.message : 'unknown'));
					}
				},
				onfailure: function () {
					alert('Ошибка соединения');
				}
			});
		}
	};

	if (typeof BX !== 'undefined' && typeof BX.ready === 'function')
	{
		BX.ready(OtusTmStart.init);
	}
	else
	{
		document.addEventListener('DOMContentLoaded', OtusTmStart.init);
	}
})();
