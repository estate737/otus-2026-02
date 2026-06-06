BX.addCustomEvent("onTimeManWindowOpen", function () {
	var tmWindow = this;

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
							return;
						}

						var data = parent.DATA || {};
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
					click: function () {
						popup.close();
					}
				}
			})
		]
	});
	popup.show();
});
