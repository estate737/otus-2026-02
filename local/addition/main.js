(function () {
	'use strict';

	if (window.OtusTmStart)
	{
		return;
	}

	var STATE = { state: '', canOpen: '' };
	var openedPopup = null;
	var wrapped = false;
	var bypass = false;

	function log()
	{
		try { console.log.apply(console, ['[OtusTmStart]'].concat([].slice.call(arguments))); } catch (e) {}
	}

	function readInitialState()
	{
		var p = window.BXTIMEMAN;
		if (p && p.DATA)
		{
			if (p.DATA.STATE) STATE.state = String(p.DATA.STATE).toUpperCase();
			if (p.DATA.CAN_OPEN) STATE.canOpen = String(p.DATA.CAN_OPEN).toUpperCase();
		}
	}

	function applyPullState(info)
	{
		if (!info) return;
		if (info.state) STATE.state = String(info.state).toUpperCase();
		STATE.canOpen = info.action ? String(info.action).toUpperCase() : '';
		log('PULL state =', STATE.state, 'canOpen =', STATE.canOpen);
	}

	function chooseAction()
	{
		if (STATE.state === 'PAUSED') return 'REOPEN';
		if (STATE.state === 'CLOSED' && STATE.canOpen === 'REOPEN') return 'REOPEN';
		return 'OPEN';
	}

	function fireAction(action)
	{
		var p = window.BXTIMEMAN;
		if (!p) { log('no BXTIMEMAN'); return; }

		if (p.WND && p.WND.ACTIONS && typeof p.WND.ACTIONS[action] === 'function')
		{
			log('fire WND.ACTIONS.' + action);
			p.WND.ACTIONS[action]();
		}
		else if (action === 'REOPEN' && typeof p.ReOpenDay === 'function')
		{
			p.ReOpenDay();
		}
		else if (typeof p.OpenDay === 'function')
		{
			p.OpenDay();
		}

		BX.ajax({
			url: '/homeworks/homework8/notify.php',
			method: 'POST',
			dataType: 'json',
			data: { sessid: BX.bitrix_sessid(), action: action }
		});
	}

	function hideStandardWindow()
	{
		var p = window.BXTIMEMAN;
		if (p && p.WND && typeof p.WND.Hide === 'function')
		{
			try { p.WND.Hide(); } catch (e) {}
		}
	}

	function notify(text)
	{
		if (BX.UI && BX.UI.Notification && BX.UI.Notification.Center)
		{
			BX.UI.Notification.Center.notify({ content: text, autoHideDelay: 4000 });
		}
		else
		{
			log('NOTIFY:', text);
		}
	}

	function showConfirm()
	{
		var action = chooseAction();
		var paused = action === 'REOPEN';
		var title = paused ? 'Продолжить рабочий день?' : 'Начать рабочий день?';
		var lead = paused
			? 'Рабочий день стоит на паузе / закрыт. Возобновить учет времени?'
			: 'Вы собираетесь <b>начать рабочий день</b>. После подтверждения учет рабочего времени будет запущен.';
		var btnText = paused ? 'Продолжить' : 'Начать рабочий день';

		var content = '<div style="padding:15px 5px; line-height:1.55; font-size:14px;">'
			+ lead + '<br><br>'
			+ '<span style="color:#828b95;">Закройте окно, если передумали - ничего не произойдет.</span>'
			+ '</div>';

		if (openedPopup && typeof openedPopup.destroy === 'function')
		{
			try { openedPopup.destroy(); } catch (e) {}
		}

		if (typeof BX === 'undefined' || !BX.PopupWindowManager)
		{
			log('BX.PopupWindowManager not loaded, falling back to alert');
			if (confirm(title)) { fireAction(action); }
			return;
		}

		openedPopup = BX.PopupWindowManager.create('otus-tm-start-popup', null, {
			titleBar: title,
			content: content,
			width: 440,
			closeIcon: true,
			overlay: true,
			buttons: [
				new BX.PopupWindowButton({
					text: btnText,
					className: 'ui-btn ui-btn-success',
					events: {
						click: function () {
							openedPopup.close();
							fireAction(action);
						}
					}
				}),
				new BX.PopupWindowButtonLink({
					text: 'Отмена',
					className: 'ui-btn ui-btn-link',
					events: {
						click: function () { openedPopup.close(); }
					}
				})
			]
		});
		openedPopup.show();
		log('confirm popup shown, action=', action);
	}

	function wrapBXTIMEMAN()
	{
		if (wrapped) return true;
		var p = window.BXTIMEMAN;
		if (!p || typeof p.Open !== 'function') return false;

		var originalOpen = p.Open;
		p.__otusOriginalOpen = originalOpen;
		p.Open = function () {
			if (bypass)
			{
				bypass = false;
				return originalOpen.apply(p, arguments);
			}
			readInitialState();
			log('BXTIMEMAN.Open() intercepted; state=', STATE.state, 'canOpen=', STATE.canOpen);

			if (STATE.state === 'OPENED')
			{
				return originalOpen.apply(p, arguments);
			}

			hideStandardWindow();
			showConfirm();
		};

		wrapped = true;
		log('BXTIMEMAN.Open wrapped');
		return true;
	}

	function subscribeToPull()
	{
		if (!window.BX || !BX.PULL || typeof BX.PULL.subscribe !== 'function')
		{
			log('BX.PULL not available, skipping subscription');
			return;
		}

		BX.PULL.subscribe({
			moduleId: 'timeman',
			callback: function (data) {
				log('PULL timeman:', data.command, data.params);
				if (data && data.params && data.params.info)
				{
					applyPullState(data.params.info);
				}
			}
		});

		BX.PULL.subscribe({
			moduleId: 'otus.homework8',
			callback: function (data) {
				log('PULL otus.homework8:', data.command, data.params);
				if (data && data.command === 'workdayConfirmed')
				{
					var msg = data.params && data.params.message
						? data.params.message
						: 'Рабочий день инициирован через ДЗ #8';
					notify(msg);
				}
			}
		});

		if (typeof BX.PULL.extendWatch === 'function')
		{
			BX.PULL.extendWatch('otus.homework8');
		}

		log('PULL subscribed');
	}

	function waitAndWrap()
	{
		if (wrapBXTIMEMAN()) return;
		var tries = 0;
		var iv = setInterval(function () {
			if (wrapBXTIMEMAN() || ++tries > 100)
			{
				clearInterval(iv);
				if (!wrapped) log('BXTIMEMAN not appeared in 10s');
			}
		}, 100);
	}

	window.OtusTmStart = {
		init: function () {
			if (typeof BX === 'undefined') return;
			readInitialState();
			waitAndWrap();
			subscribeToPull();
			log('init done; state=', STATE.state, 'canOpen=', STATE.canOpen);
		},
		test: function () { showConfirm(); },
		state: function () { return STATE; }
	};

	if (typeof BX !== 'undefined' && typeof BX.ready === 'function')
	{
		BX.ready(window.OtusTmStart.init);
	}
	else
	{
		document.addEventListener('DOMContentLoaded', window.OtusTmStart.init);
	}
})();
