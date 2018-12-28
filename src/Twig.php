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
use Berlioz\Core\CoreAwareInterface;
use Berlioz\Core\CoreAwareTrait;
use Berlioz\Core\Debug;

/**
 * Class Twig.
 *
 * @package Berlioz\Package\Twig
 */
class Twig implements CoreAwareInterface
{
    use CoreAwareTrait;
    /** @var \Twig_Loader_Chain */
    private $loader;
    /** @var \Twig_Environment */
    private $twig;

    /**
     * Twig constructor.
     *
     * @param \Berlioz\Core\Core $core       Berlioz Core
     * @param array              $paths      Twig paths
     * @param array              $options    Twig options
     * @param string[]           $extensions Twig extensions classes
     * @param array              $globals    Globals variables
     *
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Berlioz\ServiceContainer\Exception\ContainerException
     * @throws \Berlioz\ServiceContainer\Exception\InstantiatorException
     * @throws \Twig_Error_Loader
     */
    public function __construct(
        Core $core,
        array $paths = [],
        array $options = [],
        array $extensions = [],
        array $globals = []
    ) {
        $this->setCore($core);

        // Twig
        $this->loader = new \Twig_Loader_Chain();
        $this->loader->addLoader($fileLoader = new \Twig_Loader_Filesystem([], $this->getCore()->getDirectories()->getAppDir()));
        $this->twig = new \Twig_Environment($this->loader, $options);

        // Debug?
        if ($options['debug'] ?? false) {
            $this->getEnvironment()->addExtension(new \Twig_Extension_Debug);
        }

        // Paths
        foreach ($paths as $namespace => $path) {
            $fileLoader->addPath($path, $namespace);
        }

        // Add extensions
        $extensions = array_unique($extensions);
        foreach ($extensions as $extension) {
            if (!is_object($extension)) {
                $extension = $this->getCore()
                                  ->getServiceContainer()
                                  ->getInstantiator()
                                  ->newInstanceOf($extension,
                                                  ['templating' => $this,
                                                   'twigLoader' => $this->loader,
                                                   'twig'       => $this->twig]);
            }

            $this->getEnvironment()->addExtension($extension);
        }

        // Add globals
        foreach ($globals as $name => $value) {
            $this->getCore()
                 ->getServiceContainer()
                 ->getInstantiator()
                 ->invokeMethod($this->getEnvironment(),
                                'addGlobal',
                                ['name' => $name, 'value' => $value]);
        }
    }

    /**
     * __debugInfo() PHP magic method.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return ['loader' => '*TWIG LOADER*', 'twig' => '*TWIG*'];
    }

    /**
     * Get Twig loader.
     *
     * @return \Twig_Loader_Chain
     */
    public function getLoader(): \Twig_Loader_Chain
    {
        return $this->loader;
    }

    /**
     * Get Twig environment.
     *
     * @return \Twig_Environment
     */
    public function getEnvironment(): \Twig_Environment
    {
        return $this->twig;
    }

    /**
     * @inheritdoc
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Twig_Error Twig errors
     */
    public function render(string $name, array $variables = []): string
    {
        $twigActivity =
            (new Debug\Activity('Twig rendering'))
                ->start()
                ->setDescription(sprintf('Rendering of template "%s"', $name));

        // Twig rendering
        $str = $this->getEnvironment()->render($name, $variables);

        // Debug
        $this->getCore()->getDebug()->getTimeLine()->addActivity($twigActivity->end());

        return $str;
    }

    /**
     * @inheritdoc
     * @throws \Twig_Error Twig errors
     */
    public function hasBlock(string $name, string $blockName): bool
    {
        $template = $this->getEnvironment()->load($name);

        return $template->hasBlock($blockName);
    }

    /**
     * @inheritdoc
     * @throws \Twig_Error Twig errors
     * @throws \Throwable
     */
    public function renderBlock(string $name, string $blockName, array $variables = []): string
    {
        $twigActivity =
            (new Debug\Activity('Twig block rendering'))
                ->start()
                ->setDescription(sprintf('Rendering of block "%s" in template "%s"',
                                         $blockName,
                                         $name));

        // Twig rendering
        $template = $this->getEnvironment()->load($name);
        $str = $template->renderBlock($blockName, $variables);

        // Debug
        $this->getCore()->getDebug()->getTimeLine()->addActivity($twigActivity->end());

        return $str;
    }
}