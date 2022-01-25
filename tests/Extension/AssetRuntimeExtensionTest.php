<?php
/*
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2021 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

namespace Berlioz\Package\Twig\Tests\Extension;

use Berlioz\Core\Asset\Assets;
use Berlioz\Package\Twig\Extension\AssetRuntimeExtension;
use PHPUnit\Framework\TestCase;
use Twig\Error\RuntimeError;

class AssetRuntimeExtensionTest extends TestCase
{
    private Assets $assets;

    protected function setUp(): void
    {
        $this->assets = new Assets(
            __DIR__ . '/data/manifest.json',
            __DIR__ . '/data/entrypoints.json',
        );
    }

    public function testAsset()
    {
        $extensionRuntime = new AssetRuntimeExtension($this->assets);

        $this->assertEquals('/assets/css/website.css', $extensionRuntime->asset('website.css'));
    }

    public function testAsset_notFound()
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Asset "fake.css" not found in manifest file');

        $extensionRuntime = new AssetRuntimeExtension($this->assets);
        $extensionRuntime->asset('fake.css');
    }

    public function testEntryPoints()
    {
        $extensionRuntime = new AssetRuntimeExtension($this->assets);

        $this->assertEquals(
            '<link rel="stylesheet" href="/assets/css/website.css">' . PHP_EOL .
            '<script src="/assets/js/website.js"></script>' . PHP_EOL .
            '<script src="/assets/js/vendor.js"></script>' . PHP_EOL,
            $extensionRuntime->entryPoints('website')
        );
    }

    public function testEntryPoints_withMultipleEntry()
    {
        $extensionRuntime = new AssetRuntimeExtension($this->assets);

        $this->assertEquals(
            '<link rel="stylesheet" href="/assets/css/website.css">' . PHP_EOL .
            '<link rel="stylesheet" href="/assets/css/admin.css">' . PHP_EOL .
            '<script src="/assets/js/website.js"></script>' . PHP_EOL .
            '<script src="/assets/js/vendor.js"></script>' . PHP_EOL .
            '<script src="/assets/js/admin.js"></script>' . PHP_EOL,
            $extensionRuntime->entryPoints(['website', 'admin'])
        );
    }

    public function testEntryPoints_withType()
    {
        $extensionRuntime = new AssetRuntimeExtension($this->assets);

        $this->assertEquals(
            '<script src="/assets/js/website.js"></script>' . PHP_EOL .
            '<script src="/assets/js/vendor.js"></script>' . PHP_EOL,
            $extensionRuntime->entryPoints('website', 'js')
        );
    }

    public function testEntryPoints_withOptions()
    {
        $extensionRuntime = new AssetRuntimeExtension($this->assets);

        $this->assertEquals(
            '<link rel="stylesheet" href="/assets/css/website.css">' . PHP_EOL .
            '<script src="/assets/js/website.js" defer async></script>' . PHP_EOL .
            '<script src="/assets/js/vendor.js" defer async></script>' . PHP_EOL,
            $extensionRuntime->entryPoints('website', options: ['async' => true, 'defer' => true])
        );
    }

    public function testEntryPoints_notFound()
    {
        $extensionRuntime = new AssetRuntimeExtension($this->assets);

        $this->assertEquals('', $extensionRuntime->entryPoints('fake'));
    }

    public function testEntryPointsList()
    {
        $extensionRuntime = new AssetRuntimeExtension($this->assets);

        $this->assertEquals(
            ['css' => ['/assets/css/website.css'], 'js' => ['/assets/js/website.js', '/assets/js/vendor.js']],
            $extensionRuntime->entryPointsList('website')
        );
    }

    public function testEntryPointsList_withMultipleEntry()
    {
        $extensionRuntime = new AssetRuntimeExtension($this->assets);

        $this->assertEquals(
            ['/assets/js/website.js', '/assets/js/vendor.js', '/assets/js/admin.js'],
            $extensionRuntime->entryPointsList(['website', 'admin'], 'js')
        );
    }

    public function testEntryPointsList_withType()
    {
        $extensionRuntime = new AssetRuntimeExtension($this->assets);

        $this->assertEquals(
            ['/assets/js/website.js', '/assets/js/vendor.js'],
            $extensionRuntime->entryPointsList('website', 'js')
        );
    }

    public function testEntryPointsList_notFound()
    {
        $extensionRuntime = new AssetRuntimeExtension($this->assets);

        $this->assertEquals([], $extensionRuntime->entryPointsList('fake'));
    }
}
