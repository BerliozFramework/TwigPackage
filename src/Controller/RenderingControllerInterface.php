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

declare(strict_types=1);

namespace Berlioz\Package\Twig\Controller;

use Berlioz\Core\CoreAwareInterface;
use Berlioz\Core\Exception\BerliozException;
use Twig\Error\Error;

/**
 * Interface RenderingControllerInterface.
 *
 * @package Berlioz\Package\Twig\Controller
 */
interface RenderingControllerInterface extends CoreAwareInterface
{
    /**
     * Render a template.
     *
     * @param string $name Filename of template
     * @param mixed[] $variables Variables for template
     *
     * @return string Output content
     * @throws BerliozException
     * @throws Error
     */
    public function render(string $name, array $variables = []): string;

    /**
     * Render a block in template.
     *
     * @param string $name Filename of template
     * @param string $blockName Block name
     * @param array $variables Variables
     *
     * @return string
     * @throws BerliozException
     * @throws Error
     */
    public function renderBlock(string $name, string $blockName, array $variables = []): string;
}