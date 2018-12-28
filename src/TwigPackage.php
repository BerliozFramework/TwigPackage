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

namespace Berlioz\Package\Twig;

use Berlioz\Core\Core;
use Berlioz\Core\Package\AbstractPackage;
use Berlioz\ServiceContainer\Service;

/**
 * Class TwigPackage.
 *
 * @package Berlioz\Package\Twig
 */
class TwigPackage extends AbstractPackage
{
    /**
     * @inheritdoc
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Berlioz\ServiceContainer\Exception\ContainerException
     */
    public function register()
    {
        // Merge configuration
        $this->mergeConfig(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'resources', 'config.default.json']));

        // Create router service
        $twigService = new Service(Twig::class, 'twig');
        $twigService->setFactory(TwigPackage::class . '::twigFactory');
        $this->getCore()->getServiceContainer()->add($twigService);
    }

    /////////////////
    /// FACTORIES ///
    /////////////////

    /**
     * Twig factory.
     *
     * @param \Berlioz\Core\Core $core
     *
     * @return \Berlioz\Package\Twig\Twig
     * @throws \Berlioz\Config\Exception\ConfigException
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Berlioz\ServiceContainer\Exception\ContainerException
     * @throws \Berlioz\ServiceContainer\Exception\InstantiatorException
     */
    public static function twigFactory(Core $core): Twig
    {
        return $core->getServiceContainer()
                    ->getInstantiator()
                    ->newInstanceOf(Twig::class,
                                    $core->getConfig()->get('twig', []));
    }
}