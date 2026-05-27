/**
 * Виджет записи на процедуру врача.
 * Открывает попап (BX.PopupWindow) с формой пациента и создаёт бронирование.
 */
(function () {
	'use strict';

	if (typeof window.OtusBooking !== 'undefined')
	{
		return;
	}

	window.OtusBooking = {
		/**
		 * Открыть попап записи по клику на ссылку процедуры.
		 * @param {HTMLElement} link
		 */
		openPopup: function (link) {
			var doctorId = link.getAttribute('data-doctor-id');
			var procedureId = link.getAttribute('data-procedure-id');
			var procedureName = link.getAttribute('data-procedure-name');

			var content =
				'<div style="min-width:320px;">' +
					'<div style="margin-bottom:10px;color:#535c69;">Процедура: <b>' + BX.util.htmlspecialchars(procedureName) + '</b></div>' +
					'<div class="ui-ctl ui-ctl-textbox ui-ctl-w100" style="margin-bottom:10px;">' +
						'<input type="text" class="ui-ctl-element otus-booking-fio" placeholder="ФИО пациента">' +
					'</div>' +
					'<div class="ui-ctl ui-ctl-datetime ui-ctl-w100" style="margin-bottom:10px;">' +
						'<input type="datetime-local" class="ui-ctl-element otus-booking-time">' +
					'</div>' +
				'</div>';

			// уникальный попап на каждый вызов, со старым уничтожением
			var popup = BX.PopupWindowManager.create('otus-booking-popup', null, {
				titleBar: 'Запись на процедуру',
				content: content,
				width: 380,
				closeIcon: true,
				overlay: true,
				draggable: { restrict: true },
				buttons: [
					new BX.PopupWindowButton({
						text: 'Записать',
						className: 'ui-btn ui-btn-success',
						events: {
							click: function () {
								OtusBooking.submit(popup, doctorId, procedureId, procedureName);
							}
						}
					}),
					new BX.PopupWindowButtonLink({
						text: 'Отмена',
						className: 'ui-btn ui-btn-link',
						events: {
							click: function () { popup.close(); }
						}
					})
				]
			});

			popup.setContent(content);
			popup.show();
		},

		/**
		 * Отправить данные брони на сервер.
		 * @param {BX.PopupWindow} popup
		 * @param {string} doctorId
		 * @param {string} procedureId
		 * @param {string} procedureName
		 */
		submit: function (popup, doctorId, procedureId, procedureName) {
			var container = popup.getContentContainer();
			var fioInput = container.querySelector('.otus-booking-fio');
			var timeInput = container.querySelector('.otus-booking-time');
			var fio = fioInput ? fioInput.value.trim() : '';
			var time = timeInput ? timeInput.value.trim() : '';

			if (!fio || !time)
			{
				alert('Заполните ФИО и время записи');
				return;
			}

			BX.ajax({
				url: '/homeworks/homework7/ajax.php',
				method: 'POST',
				dataType: 'json',
				data: {
					sessid: BX.bitrix_sessid(),
					doctorId: doctorId,
					procedureId: procedureId,
					procedureName: procedureName,
					fio: fio,
					time: time
				},
				onsuccess: function (response) {
					if (response && response.status === 'success')
					{
						popup.close();
						alert('Бронь создана (#' + response.id + ')');
						window.location.reload();
					}
					else
					{
						alert((response && response.message) || 'Ошибка создания брони');
					}
				},
				onfailure: function () {
					alert('Ошибка соединения');
				}
			});
		}
	};
})();
