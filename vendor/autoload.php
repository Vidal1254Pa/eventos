<?php

spl_autoload_register(function ($class) {

    $prefixes = [
        'PhpOffice\\PhpSpreadsheet\\' => __DIR__ . '/PhpSpreadsheet/',
        'Psr\\SimpleCache\\' => __DIR__ . '/Psr/SimpleCache/',
        'Psr\\Container\\' => __DIR__ . '/Psr/Container/',
    ];

    foreach ($prefixes as $prefix => $base_dir) {

        $len = strlen($prefix);

        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        $relative_class = substr($class, $len);

        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    }
});

