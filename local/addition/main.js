console.log("[Otus] main.js loaded");

(function () {
	"use strict";

	var TM_CLASSES = [
		"task-status-action_start",
		"task-status-action_finish",
		"task-status-action",
		"tm-control-panel__action",
		"timeman-widget-opener",
		"timeman-work-time-button"
	];
	var TM_IDS = [
		"buttonStartDropdownAnchor",
		"buttonStartDropdownAnchorText",
		"buttonStartDropdownAnchorDropdown",
		"bx_tm",
		"tmstatus"
	];
	var POPUP_ID = "otus-workday-popup";
	var bypass = false;
	var popupOpen = false;
	var debugClicks = false;
	var lastClickTarget = null;

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
			if (el.id)
			{
				for (var k = 0; k < TM_IDS.length; k++)
				{
					if (el.id === TM_IDS[k])
					{
						return { node: el, by: "id:" + el.id };
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

	function isContinueButton(el)
	{
		var depth = 0;
		while (el && el !== document.body && depth < 10)
		{
			if (el.id && (
				el.id.indexOf("buttonContinue") === 0
				|| el.id.indexOf("buttonRestart") === 0
			))
			{
				return true;
			}
			var txt = (el.textContent || "").trim();
			if (txt && txt.length < 60 && (
				txt.indexOf("Продолжить") !== -1
				|| txt.indexOf("Возобновить") !== -1
			))
			{
				return true;
			}
			el = el.parentElement;
			depth++;
		}
		return false;
	}

	function startDay()
	{
		if (lastClickTarget && typeof lastClickTarget.click === "function")
		{
			console.log("[Otus] replay click on", lastClickTarget.id || lastClickTarget.className);
			bypass = true;
			lastClickTarget.click();
			return;
		}

		var p = window.BXTIMEMAN;
		if (!p) { console.log("[Otus] no BXTIMEMAN"); return; }
		bypass = true;
		var s = getState();
		console.log("[Otus] fallback OpenDay/ReOpenDay; state=", s.state, "canOpen=", s.canOpen);
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
		if (!window.BXTIMEMAN)
		{
			console.log("[Otus] no BXTIMEMAN on this page, skip popup");
			return;
		}
		if (!BX.PopupWindowManager)
		{
			console.log("[Otus] BX.PopupWindowManager not available");
			return;
		}
		if (popupOpen)
		{
			console.log("[Otus] popup already open, skip");
			return;
		}

		var s = getState();
		var continueMode = (s.state === "PAUSED" || (s.state === "CLOSED" && s.canOpen === "REOPEN"));
		if (!continueMode && lastClickTarget)
		{
			continueMode = isContinueButton(lastClickTarget);
		}
		var title = continueMode ? "Возобновить рабочий день?" : "Начать рабочий день?";
		var body = continueMode
			? "Вы собираетесь возобновить рабочий день. После подтверждения учет рабочего времени будет продолжен."
			: "Вы собираетесь начать рабочий день. После подтверждения учет рабочего времени будет запущен.";
		var btn = continueMode ? "Возобновить" : "Запустить";

		var popup = BX.PopupWindowManager.create(POPUP_ID, null, {
			titleBar: title,
			content: '<div style="padding:20px; line-height:1.55; font-size:14px;">'
				+ body + "<br><br>"
				+ '<span style="color:#828b95;">Закройте окно, если передумали - ничего не произойдет.</span>'
				+ "</div>",
			width: 420,
			closeIcon: true,
			overlay: true,
			events: {
				onPopupShow: function () { popupOpen = true; },
				onPopupClose: function () { popupOpen = false; }
			},
			buttons: [
				new BX.PopupWindowButton({
					text: btn,
					className: "ui-btn ui-btn-success otus-popup-btn",
					events: { click: function () { popup.close(); startDay(); } }
				}),
				new BX.PopupWindowButton({
					text: "Отмена",
					className: "ui-btn ui-btn-light otus-popup-btn otus-popup-btn--cancel",
					events: { click: function () { popup.close(); } }
				})
			]
		});
		popupOpen = true;
		popup.show();
		console.log("[Otus] custom popup shown; continueMode=", continueMode);
	}

	function isPopupOwnButton(el)
	{
		var depth = 0;
		while (el && el !== document.body && depth < 6)
		{
			if (el.classList && el.classList.contains("popup-window-button"))
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

		if (popupOpen || isPopupOwnButton(e.target))
		{
			return;
		}

		var hit = findTmTarget(e.target);
		if (!hit) return;

		if (bypass)
		{
			bypass = false;
			console.log("[Otus] bypass click");
			return;
		}

		if (!window.BXTIMEMAN)
		{
			return;
		}

		var s = getState();
		console.log("[Otus] intercepted click via", hit.by, "; class=", classesOf(hit.node), "; state=", s.state);

		if (s.state === "OPENED")
		{
			console.log("[Otus] день уже OPENED, пропускаем (пауза/стоп)");
			return;
		}

		lastClickTarget = e.target;
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
	console.log("[Otus] click capture installed; classes:", TM_CLASSES.join(", "), "; ids:", TM_IDS.join(", "));
})();
