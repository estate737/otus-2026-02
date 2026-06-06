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
	var TM_TEXT = ["Начать рабочий день", "Продолжить", "Возобновить"];
	var bypass = false;
	var debugClicks = false;

	function classesOf(el)
	{
		if (!el) return "";
		if (typeof el.className === "string") return el.className;
		if (el.classList) return Array.prototype.join.call(el.classList, " ");
		return "";
	}

	function findTmTarget(el)
	{
		var depth = 0;
		while (el && el !== document.body && depth < 10)
		{
			if (el.classList)
			{
				for (var i = 0; i < TM_CLASSES.length; i++)
				{
					if (el.classList.contains(TM_CLASSES[i]))
					{
						return { node: el, by: "class:" + TM_CLASSES[i] };
					}
				}
			}
			if (el.id === "bx_tm" || el.id === "tmstatus")
			{
				return { node: el, by: "id:" + el.id };
			}
			var txt = (el.textContent || "").trim();
			if (txt.length < 60)
			{
				for (var j = 0; j < TM_TEXT.length; j++)
				{
					if (txt.indexOf(TM_TEXT[j]) !== -1)
					{
						return { node: el, by: "text:" + TM_TEXT[j] };
					}
				}
			}
			el = el.parentElement;
			depth++;
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
		if (!p) { console.log("[Otus] no BXTIMEMAN"); return; }
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
				+ body + "<br><br>"
				+ '<span style="color:#828b95;">Закройте окно, если передумали - ничего не произойдет.</span>'
				+ "</div>",
			width: 420,
			closeIcon: true,
			overlay: true,
			buttons: [
				new BX.PopupWindowButton({
					text: btn,
					className: "ui-btn ui-btn-success",
					events: { click: function () { popup.close(); startDay(); } }
				}),
				new BX.PopupWindowButtonLink({
					text: "Отмена",
					events: { click: function () { popup.close(); } }
				})
			]
		});
		popup.show();
		console.log("[Otus] custom popup shown; continueMode=", continueMode);
	}

	function isInsidePopup(el)
	{
		var depth = 0;
		while (el && el !== document.body && depth < 30)
		{
			if (el.classList && (
				el.classList.contains("popup-window")
				|| el.classList.contains("popup-window-buttons")
				|| el.classList.contains("popup-window-button")
				|| el.classList.contains("popup-window-overlay")
			))
			{
				return true;
			}
			el = el.parentElement;
			depth++;
		}
		return false;
	}

	document.addEventListener("click", function (e) {
		if (debugClicks)
		{
			console.log(
				"[Otus DEBUG click] tag=", e.target.tagName,
				"id=", e.target.id,
				"class=", classesOf(e.target),
				"text=", (e.target.textContent || "").trim().slice(0, 60)
			);
		}

		if (isInsidePopup(e.target))
		{
			return;
		}

		var hit = findTmTarget(e.target);
		if (!hit) return;

		var s = getState();
		console.log("[Otus] intercepted click via", hit.by, "; class=", classesOf(hit.node), "; state=", s.state);

		if (s.state === "OPENED")
		{
			console.log("[Otus] day is OPENED, let standard flow run (pause/stop)");
			return;
		}

		e.stopPropagation();
		e.preventDefault();
		showPopup();
	}, true);

	if (typeof BX !== "undefined" && typeof BX.addCustomEvent === "function")
	{
		BX.addCustomEvent("onTimeManWindowOpen", function () {
			var tmWindow = this;
			if (bypass) { bypass = false; return; }
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
	window.otusDebugClicks = function (on) { debugClicks = (on !== false); console.log("[Otus] debugClicks=", debugClicks); };
	console.log("[Otus] click capture installed; classes:", TM_CLASSES.join(", "), "; text triggers:", TM_TEXT.join(", "));
	console.log("[Otus] use otusDebugClicks(true) to log every click target");
})();
