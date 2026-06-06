console.log("[Otus] main.js loaded");

window.otusTestPopup = function () {
	if (typeof BX === "undefined" || !BX.PopupWindowManager)
	{
		console.log("[Otus] BX.PopupWindowManager NOT available");
		return;
	}
	var p = BX.PopupWindowManager.create("otus-workday-popup", null, {
		titleBar: "Тест: попап работает",
		content: '<div style="padding:20px;">Если ты это видишь - PopupWindowManager доступен.</div>',
		width: 400,
		closeIcon: true,
		overlay: true,
		buttons: [
			new BX.PopupWindowButton({
				text: "OK",
				className: "ui-btn ui-btn-success",
				events: { click: function () { p.close(); } }
			})
		]
	});
	p.show();
};

if (typeof BX !== "undefined" && typeof BX.addCustomEvent === "function")
{
	BX.addCustomEvent("onTimeManWindowOpen", function () {
		var tmWindow = this;
		console.log("[Otus] onTimeManWindowOpen fired", tmWindow);

		if (window.__otusBypass)
		{
			window.__otusBypass = false;
			return;
		}

		setTimeout(function () {
			if (tmWindow && typeof tmWindow.Hide === "function")
			{
				tmWindow.Hide();
			}
		}, 0);

		if (!BX.PopupWindowManager)
		{
			console.log("[Otus] BX.PopupWindowManager not loaded");
			return;
		}

		var popup = BX.PopupWindowManager.create("otus-workday-popup", null, {
			titleBar: "Начать рабочий день?",
			content: '<div style="padding:20px; line-height:1.55; font-size:14px;">'
				+ "Вы собираетесь начать рабочий день. После подтверждения учет рабочего времени будет запущен."
				+ "<br><br>"
				+ '<span style="color:#828b95;">Закройте окно, если передумали - ничего не произойдет.</span>'
				+ "</div>",
			width: 420,
			closeIcon: true,
			overlay: true,
			buttons: [
				new BX.PopupWindowButton({
					text: "Начать рабочий день",
					className: "ui-btn ui-btn-success",
					events: {
						click: function () {
							popup.close();
							var parent = tmWindow && tmWindow.PARENT;
							if (!parent)
							{
								console.log("[Otus] no tmWindow.PARENT");
								return;
							}
							var data = parent.DATA || {};
							console.log("[Otus] starting workday; STATE=", data.STATE, "CAN_OPEN=", data.CAN_OPEN);
							if (data.STATE === "PAUSED" || (data.STATE === "CLOSED" && data.CAN_OPEN === "REOPEN"))
							{
								parent.ReOpenDay();
							}
							else
							{
								parent.OpenDay();
							}
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
		console.log("[Otus] custom popup shown");
	});
	console.log("[Otus] subscribed to onTimeManWindowOpen");
}
else
{
	console.log("[Otus] BX.addCustomEvent NOT available");
}
