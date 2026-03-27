<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

\App\Debug\Log::cleanLog('exceptions');

LocalRedirect('/homeworks/homework2/');
