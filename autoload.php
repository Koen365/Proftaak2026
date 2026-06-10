<?php
spl_autoload_register(function ($class) {
    $prefixes = [
        'Classes\\' => __DIR__ . '/classes/',
        'Config\\'  => __DIR__ . '/config/',
    ];
    foreach ($prefixes as $prefix => $base_dir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) === 0) {
            $file = $base_dir . str_replace('\\', '/', substr($class, $len)) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});
