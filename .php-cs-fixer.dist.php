<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = (new Finder())
    ->in([
        __DIR__ . '/custom/plugins/SwProductLabel/src',
        __DIR__ . '/custom/plugins/SwProductLabel/tests',
    ]);

return (new Config())
    ->setRules([
        '@PER-CS2x0' => true,
        'no_unused_imports' => true,
    ])
    ->setFinder($finder);
