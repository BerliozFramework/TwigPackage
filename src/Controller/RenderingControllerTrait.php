<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2018 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\Package\Twig\Controller;

use Berlioz\Core\Core;
use Berlioz\Package\Twig\Twig;

/**
 * Trait RenderingControllerTrait.
 *
 * @package Berlioz\Package\Twig\Controller
 */
trait RenderingControllerTrait
{
    /**
     * Get core.
     *
     * @return \Berlioz\Core\Core|null
     */
    abstract public function getCore(): ?Core;

    /**
     * Render a template.
     *
     * @param string  $name      Filename of template
     * @param mixed[] $variables Variables for template
     *
     * @return string Output content
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Twig\Error\Error
     */
    public function render(string $name, array $variables = []): string
    {
        /** @var \Berlioz\Package\Twig\Twig $twig */
        $twig = $this->getCore()->getServiceContainer()->get(Twig::class);

        return $twig->render($name, $variables);
    }

    /**
     * Render a block in template.
     *
     * @param string $name      Filename of template
     * @param string $blockName Block name
     * @param array  $variables Variables
     *
     * @return string
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Twig\Error\Error
     */
    public function renderBlock(string $name, string $blockName, array $variables = []): string
    {
        /** @var \Berlioz\Package\Twig\Twig $twig */
        $twig = $this->getCore()->getServiceContainer()->get(Twig::class);

        return $twig->renderBlock($name, $blockName, $variables);
    }
}