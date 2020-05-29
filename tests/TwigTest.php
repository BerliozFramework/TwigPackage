<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2020 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

namespace Berlioz\Package\Twig\Tests;

use Berlioz\Core\Core;
use Berlioz\Package\Twig\TestProject\FakeDefaultDirectories;
use Berlioz\Package\Twig\TestProject\Service;
use Berlioz\Package\Twig\Twig;
use Berlioz\Package\Twig\TwigExtension;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;

class TwigTest extends TestCase
{
    public function test()
    {
        $core = new Core(new FakeDefaultDirectories(), false);
        $twig = new Twig(
            $core,
            $namespaces =
                [
                    'foo' => realpath(__DIR__ . '/_envTest/templates/foo'),
                    'bar' => realpath(__DIR__ . '/_envTest/templates/bar'),
                ],
            [
                'debug' => true,
                'cache' => false,
            ],
            [TwigExtension::class],
            $globals =
                [
                    'foo' => 'bar',
                    'bar' => 'foo',
                ]
        );

        $this->assertSame($core, $twig->getCore());
        $this->assertInstanceOf(Environment::class, $twig->getEnvironment());
        $this->assertContains(
            TwigExtension::class,
            array_map(
                function ($value) {
                    return get_class($value);
                },
                $twig->getEnvironment()->getExtensions()
            )
        );

        /** @var ChainLoader $chainLoader */
        $chainLoader = $twig->getEnvironment()->getLoader();
        $this->assertInstanceOf(ChainLoader::class, $chainLoader);
        $this->assertSame($twig->getLoader(), $chainLoader);
        $this->assertCount(1, $chainLoader->getLoaders());

        /** @var FilesystemLoader $fileLoader */
        $fileLoader = $chainLoader->getLoaders()[0];
        $this->assertInstanceOf(FilesystemLoader::class, $fileLoader);
        $this->assertEquals(array_keys($namespaces), $fileLoader->getNamespaces());

        $this->assertEquals($globals, $twig->getEnvironment()->getGlobals());
        $this->assertTrue($twig->getEnvironment()->isDebug());
        $this->assertFalse($twig->getEnvironment()->getCache());
    }

    public function testDynamicGlobal()
    {
        $core = new Core(new FakeDefaultDirectories(), false);
        $twig = new Twig(
            $core,
            [],
            [],
            [],
            ['service' => '@service']
        );

        $this->assertInstanceOf(Service::class, $twig->getEnvironment()->getGlobals()['service']);
        $this->assertSame(
            $core->getServiceContainer()->get(Service::class),
            $twig->getEnvironment()->getGlobals()['service']
        );
    }

    public function testRender()
    {
        $core = new Core(new FakeDefaultDirectories(), false);
        $twig = new Twig(
            $core,
            ['bar' => realpath(__DIR__ . '/_envTest/templates/bar')],
            ['debug' => true, 'cache' => false]
        );

        $this->assertEquals('FOOBAR', $twig->render('@bar/bar.html.twig'));
        $this->assertEquals('FOOBARQUX', $twig->render('@bar/bar.html.twig', ['variable' => 'QUX']));

        $this->assertTrue($twig->hasBlock('@bar/bar.html.twig', 'bar'));
        $this->assertTrue($twig->hasBlock('@bar/bar.html.twig', 'foo'));
        $this->assertFalse($twig->hasBlock('@bar/bar.html.twig', 'qux'));

        $this->assertEquals('BAR', $twig->renderBlock('@bar/bar.html.twig', 'bar'));
        $this->assertEquals('BARQUX', $twig->renderBlock('@bar/bar.html.twig', 'bar', ['variable2' => 'QUX']));

        $this->expectException(LoaderError::class);
        $this->assertFalse($twig->hasBlock('@bar/qux.html.twig', 'qux'));
    }
}
