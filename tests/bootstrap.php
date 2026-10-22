<?php

declare(strict_types=1);

$autoload = $argv[1] ?? dirname(__DIR__).'/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Install dependencies or pass the path to a Contao vendor/autoload.php.\n");
    exit(1);
}

require $autoload;

// Prefer this checkout even when the supplied installation has an optimized
// Composer class map for another version of the bundle.
spl_autoload_register(static function (string $class): void {
    $prefix = 'HeimrichHannot\\LoginRegistrationBundle\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $file = dirname(__DIR__).'/src/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
    if (is_file($file)) {
        require $file;
    }
}, true, true);
