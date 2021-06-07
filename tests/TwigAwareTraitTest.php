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

namespace Berlioz\Package\Twig\Tests;

use Berlioz\Core\Core;
use Berlioz\Package\Twig\Exception\TwigException;
use Berlioz\Package\Twig\TestProject\FakeDefaultDirectories;
use Berlioz\Package\Twig\Twig;
use Berlioz\Package\Twig\TwigAwareTrait;
use PHPUnit\Framework\TestCase;

class TwigAwareTraitTest extends TestCase
{
    public function test()
    {
        /** @var TwigAwareTrait $trait */
        $trait = $this->getMockForTrait(TwigAwareTrait::class);

        $this->assertNull($trait->getTwig());

        $twig = new Twig(new Core(new FakeDefaultDirectories(), false));
        $trait->setTwig($twig);

        $this->assertSame($trait->getTwig(), $twig);
    }

    public function testRender()
    {
        /** @var TwigAwareTrait $trait */
        $trait = $this->getMockForTrait(TwigAwareTrait::class);

        $twig = new Twig(
            new Core(new FakeDefaultDirectories(), false),
            [
                'foo' => realpath(__DIR__ . '/../tests_env/resources/templates/foo'),
                'bar' => realpath(__DIR__ . '/../tests_env/resources/templates/bar'),
            ],
        );
        $trait->setTwig($twig);

        $this->assertSame(
            $twig->render('@bar/bar.html.twig'),
            $trait->render('@bar/bar.html.twig')
        );
    }

    public function testRender_notInit()
    {
        $this->expectException(TwigException::class);

        /** @var TwigAwareTrait $trait */
        $trait = $this->getMockForTrait(TwigAwareTrait::class);
        $trait->render('@bar/bar.html.twig');
    }

    public function testRenderBlock()
    {
        /** @var TwigAwareTrait $trait */
        $trait = $this->getMockForTrait(TwigAwareTrait::class);

        $twig = new Twig(
            new Core(new FakeDefaultDirectories(), false),
            [
                'foo' => realpath(__DIR__ . '/../tests_env/resources/templates/foo'),
                'bar' => realpath(__DIR__ . '/../tests_env/resources/templates/bar'),
            ],
        );
        $trait->setTwig($twig);

        $this->assertSame(
            $twig->renderBlock('@bar/bar.html.twig', 'bar'),
            $trait->renderBlock('@bar/bar.html.twig', 'bar')
        );
    }

    public function testRenderBlock_notInit()
    {
        $this->expectException(TwigException::class);

        /** @var TwigAwareTrait $trait */
        $trait = $this->getMockForTrait(TwigAwareTrait::class);
        $trait->renderBlock('@bar/bar.html.twig', 'bar');
    }
}
