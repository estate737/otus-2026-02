console.log("[Otus] main.js loaded");

(function () {
	"use strict";

	var TM_CLASSES = [
		"task-status-action_start",
		"task-status-action_finish",
		"task-status-action",
		"timeman-widget-opener",
		"timeman-work-time-button"
	];
	var bypass = false;

	function findTmTarget(el)
	{
		while (el && el !== document.body)
		{
			if (el.classList)
			{
				for (var i = 0; i < TM_CLASSES.length; i++)
				{
					if (el.classList.contains(TM_CLASSES[i]))
					{
						return el;
					}
				}
			}
			if (el.id === "bx_tm" || el.id === "tmstatus")
			{
				return el;
			}
			el = el.parentElement;
		}
		return null;
	}

	function getState()
	{
		var p = window.BXTIMEMAN;
		var data = (p && p.DATA) || {};
		return {
			state: (data.STATE || "").toString().toUpperCase(),
			canOpen: (data.CAN_OPEN || "").toString().toUpperCase()
		};
	}

	function startDay()
	{
		var p = window.BXTIMEMAN;
		if (!p)
		{
			console.log("[Otus] no BXTIMEMAN");
			return;
		}
		bypass = true;
		var s = getState();
		console.log("[Otus] start workday; state=", s.state, "canOpen=", s.canOpen);
		if (s.state === "PAUSED" || (s.state === "CLOSED" && s.canOpen === "REOPEN"))
		{
			if (typeof p.ReOpenDay === "function") p.ReOpenDay();
			else if (p.WND && p.WND.ACTIONS && typeof p.WND.ACTIONS.REOPEN === "function") p.WND.ACTIONS.REOPEN();
		}
		else
		{
			if (typeof p.OpenDay === "function") p.OpenDay();
			else if (p.WND && p.WND.ACTIONS && typeof p.WND.ACTIONS.OPEN === "function") p.WND.ACTIONS.OPEN();
		}
	}

	function showPopup()
	{
		if (!BX.PopupWindowManager)
		{
			console.log("[Otus] BX.PopupWindowManager not available");
			return;
		}
		var s = getState();
		var continueMode = (s.state === "PAUSED" || (s.state === "CLOSED" && s.canOpen === "REOPEN"));
		var title = continueMode ? "Продолжить рабочий день?" : "Начать рабочий день?";
		var body = continueMode
			? "Рабочий день стоит на паузе. Возобновить учет рабочего времени?"
			: "Вы собираетесь начать рабочий день. После подтверждения учет рабочего времени будет запущен.";
		var btn = continueMode ? "Продолжить" : "Начать рабочий день";

		var popup = BX.PopupWindowManager.create("otus-workday-popup", null, {
			titleBar: title,
			content: '<div style="padding:20px; line-height:1.55; font-size:14px;">'
				+ body
				+ "<br><br>"
				+ '<span style="color:#828b95;">Закройте окно, если передумали - ничего не произойдет.</span>'
				+ "</div>",
			width: 420,
			closeIcon: true,
			overlay: true,
			buttons: [
				new BX.PopupWindowButton({
					text: btn,
					className: "ui-btn ui-btn-success",
					events: {
						click: function () {
							popup.close();
							startDay();
						}
					}
				}),
				new BX.PopupWindowButtonLink({
					text: "Отмена",
					events: {
						click: function () { popup.close(); }
					}
				})
			]
		});
		popup.show();
		console.log("[Otus] custom popup shown; continueMode=", continueMode);
	}

	document.addEventListener("click", function (e) {
		var target = findTmTarget(e.target);
		if (!target)
		{
			return;
		}
		var s = getState();
		if (s.state === "OPENED")
		{
			console.log("[Otus] day is OPENED, let standard flow run (pause/stop)");
			return;
		}
		console.log("[Otus] intercepted click on", target.className || target.id, "; state=", s.state);
		e.stopPropagation();
		e.preventDefault();
		showPopup();
	}, true);

	if (typeof BX !== "undefined" && typeof BX.addCustomEvent === "function")
	{
		BX.addCustomEvent("onTimeManWindowOpen", function () {
			var tmWindow = this;
			if (bypass)
			{
				bypass = false;
				return;
			}
			console.log("[Otus] onTimeManWindowOpen fired (legacy path)");
			setTimeout(function () {
				if (tmWindow && typeof tmWindow.Hide === "function") tmWindow.Hide();
			}, 0);
			showPopup();
		});
	}

	if (window.BX && BX.PULL && typeof BX.PULL.subscribe === "function")
	{
		BX.PULL.subscribe({
			moduleId: "timeman",
			callback: function (data) {
				console.log("[Otus] PULL timeman:", data.command, data.params && data.params.info);
			}
		});
	}

	window.otusTestPopup = function () { showPopup(); };
	console.log("[Otus] click capture installed for classes:", TM_CLASSES.join(", "));
})();
