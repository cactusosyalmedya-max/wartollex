<?php
declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'Wartollex\\')) {
        $path = __DIR__ . '/../' . str_replace('Wartollex\\', '', $class) . '.php';
        $path = str_replace('\\', '/', $path);
        if (file_exists($path)) {
            require_once $path;
        }
    }
});
