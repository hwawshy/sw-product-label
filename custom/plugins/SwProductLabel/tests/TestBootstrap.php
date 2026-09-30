<?php

declare(strict_types=1);

use Shopware\Core\TestBootstrapper;

/*
 * EnvironmentHelper resolves APP_ENV from $_SERVER first, which may carry an
 * APP_ENV from the surrounding environment (e.g. the dev container). Force
 * the test environment before any kernel boot.
 */
$_SERVER['APP_ENV'] = 'test';
$_SERVER['APP_DEBUG'] = '1';

$loader = (new TestBootstrapper())
    ->addCallingPlugin()
    ->addActivePlugins('SwProductLabel')
    ->setForceInstallPlugins(true)
    ->bootstrap()
    ->getClassLoader();

$loader->addPsr4('SwProductLabel\Tests\\', __DIR__);
