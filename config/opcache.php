<?php

/**
 * OPcache preload configuration for production
 * This file preloads commonly used classes to improve performance
 */

if (!function_exists('opcache_compile_file')) {
    return;
}

$appPath = dirname(__DIR__);

// Preload Laravel framework files
$preloadFiles = [
    $appPath . '/vendor/laravel/framework/src/Illuminate/Foundation/Application.php',
    $appPath . '/vendor/laravel/framework/src/Illuminate/Container/Container.php',
    // $appPath . '/vendor/laravel/framework/src/Illuminate/Support/ServiceProvider.php',
    $appPath . '/vendor/laravel/framework/src/Illuminate/Database/Eloquent/Model.php',
    $appPath . '/vendor/laravel/framework/src/Illuminate/Http/Request.php',
    $appPath . '/vendor/laravel/framework/src/Illuminate/Http/Response.php',
];

// Preload application files
$appFiles = [
    $appPath . '/app/Models/User.php',
    $appPath . '/app/Models/HuntedDeal.php',
    $appPath . '/app/Models/Deal.php',
    $appPath . '/app/Models/DealSnapshot.php',
];

foreach (array_merge($preloadFiles, $appFiles) as $file) {
    if (file_exists($file)) {
        try {
            opcache_compile_file($file);
        } catch (Throwable $e) {
            // Ignore preload errors
        }
    }
}