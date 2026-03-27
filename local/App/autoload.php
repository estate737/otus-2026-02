<?php

spl_autoload_register(function ($className) {
    if (!str_contains($className, 'App'))
    {
        return;
    }

    $className = str_replace('App', '', $className);
    $className = str_replace('\\', '/', $className);

    $filePath = __DIR__ . $className . '.php';

    if (file_exists($filePath))
    {
        require_once $filePath;
    }
});
