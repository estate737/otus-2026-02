<?

use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #2: Отладка и логирование");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');


?>
<h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>

<h4 class="mb-3">Пояснительная записка</h4>
<div>
    Написан свой класс логгера App\Debug\Log, наследует FileExceptionHandlerLog, добавляет OTUS в каждую строку.
    Подключен в .settings_extra.php. Часть 1 - запись даты и времени в лог. Часть 2 - обработка исключений через системный логгер.
</div>
<br>
<br>
<hr>

<h4 class="mb-3">Часть 1 - Logger</h4>
<ul class="list-group">
    <li class="list-group-item">
        <a href="writelog.php">Добавление в лог из п1 ДЗ</a>
    </li>
    <li class="list-group-item">
        <a href="clearlog.php">Очистить лог из п1 ДЗ</a>
    </li>
    <li class="list-group-item">
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FDebug%2FLog.php&full_src=Y">Файл с классом кастомного логгера</a>
    </li>
</ul>
<h5 class="mt-3">Лог:</h5>
<pre class="border p-3 bg-light"><?php
$logFile = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/log_custom_' . date("d.m.Y") . '.log';
echo htmlspecialchars(file_exists($logFile) ? file_get_contents($logFile) : 'Пусто');
?></pre>


<h4 class="mb-3 mt-5">Часть 2 - Exception</h4>
<ul class="list-group">
    <li class="list-group-item">
        <a href="writeexception.php">Добавление в лог из п2 ДЗ</a>
    </li>
    <li class="list-group-item">
        <a href="clearexception.php">Очистить лог из п2 ДЗ</a>
    </li>
    <li class="list-group-item">
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FDebug%2FLog.php&full_src=Y">Файл с классом системного логгера</a>
    </li>
</ul>
<h5 class="mt-3">Лог исключений:</h5>
<pre class="border p-3 bg-light"><?php
$exceptionLogFile = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/exceptions.log';
echo htmlspecialchars(file_exists($exceptionLogFile) ? file_get_contents($exceptionLogFile) : 'Пусто');
?></pre>



<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
