<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

throw new \RuntimeException('Test exception at ' . date('d.m.Y H:i:s'));
