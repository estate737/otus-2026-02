<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

\App\Debug\Log::addLog('Текущая дата и время: ' . date("d.m.Y H:i:s"), false, 'log_custom');

LocalRedirect('/homeworks/homework2/');
