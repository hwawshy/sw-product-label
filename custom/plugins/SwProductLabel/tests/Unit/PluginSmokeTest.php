<?php

declare(strict_types=1);

namespace SwProductLabel\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SwProductLabel\SwProductLabel;

/**
 * Verifies the plugin scaffold stays intact: the plugin class is reachable
 * through the composer autoloader and its metadata matches the composer.json.
 */
class PluginSmokeTest extends TestCase
{
    public function testPluginClassIsAvailableThroughComposerAutoloader(): void
    {
        static::assertTrue(class_exists(SwProductLabel::class));
    }

    public function testComposerPluginMetadataIsConsistent(): void
    {
        $composerJsonRaw = file_get_contents(__DIR__ . '/../../composer.json');
        static::assertIsString($composerJsonRaw);

        $composerJson = json_decode($composerJsonRaw, true, 512, \JSON_THROW_ON_ERROR);
        static::assertIsArray($composerJson);

        static::assertArrayHasKey('name', $composerJson);
        static::assertSame('sw/product-label', $composerJson['name']);

        static::assertArrayHasKey('extra', $composerJson);
        $extra = $composerJson['extra'];
        static::assertIsArray($extra);
        static::assertArrayHasKey('shopware-plugin-class', $extra);
        static::assertSame('SwProductLabel\\SwProductLabel', $extra['shopware-plugin-class']);
    }
}
