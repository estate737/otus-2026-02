<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

\App\Debug\Log::cleanLog('log_custom_' . date("d.m.Y"));

LocalRedirect('/homeworks/homework2/');
