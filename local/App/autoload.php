<?php

spl_autoload_register(function ($className) {
    if (!str_contains($className, 'App') && !str_contains($className, 'Models'))
    {
        return;
    }

    if (str_contains($className, 'App'))
    {
        $path = str_replace('App', '', $className);
    }
    else
    {
        $path = $className;
    }

    $path = str_replace('\\', '/', $path);
    $filePath = __DIR__ . '/' . ltrim($path, '/') . '.php';

    if (file_exists($filePath))
    {
        require_once $filePath;
    }
});
