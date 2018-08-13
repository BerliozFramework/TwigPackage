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

use Berlioz\Core\App\AbstractApp;
use Berlioz\Core\App\AppAwareTrait;
use Berlioz\Core\Debug;
use Berlioz\Core\Package\TemplateEngine;
use Psr\Log\LoggerInterface;

class Twig implements TemplateEngine
{
    use AppAwareTrait;
    private $loader;
    private $twig;

    /**
     * Twig constructor.
     *
     * @param \Berlioz\Core\App\AbstractApp $app        Berlioz Application
     * @param array                         $paths      Twig paths
     * @param array                         $options    Twig options
     * @param string[]                      $extensions Twig extensions classes
     * @param array                         $globals    Globals variables
     *
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Berlioz\ServiceContainer\Exception\Instantiator
     * @throws \Twig_Error_Loader
     */
    public function __construct(AbstractApp $app, array $paths = [], array $options = [], array $extensions = [], array $globals = [])
    {
        $this->setApp($app);

        // Twig
        $this->loader = new \Twig_Loader_Filesystem([], $this->getApp()->getAppDir());
        $this->twig = new \Twig_Environment($this->loader, $options);
        $this->getTwig()->addExtension(new TwigExtension($this->getApp()));

        // Debug?
        if ($options['debug']) {
            $this->getTwig()->addExtension(new \Twig_Extension_Debug);
        }

        // Paths
        foreach ($paths as $namespace => $path) {
            $this->getLoader()->addPath($path, $namespace);
        }

        // Add extensions
        foreach ($extensions as $extension) {
            if (!is_object($extension)) {
                $extension = $this->getApp()
                                  ->getServiceContainer()
                                  ->getInstantiator()
                                  ->newInstanceOf($extension,
                                                  ['templating' => $this,
                                                   'twigLoader' => $this->loader,
                                                   'twig'       => $this->twig]);
            }

            $this->getTwig()->addExtension($extension);
        }

        // Add globals
        foreach ($globals as $name => $value) {
            $this->getTwig()->addGlobal($name, $value);
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
     * @return \Twig_Loader_Filesystem
     */
    public function getLoader(): \Twig_Loader_Filesystem
    {
        return $this->loader;
    }

    /**
     * Get Twig.
     *
     * @return \Twig_Environment
     */
    public function getTwig(): \Twig_Environment
    {
        return $this->twig;
    }

    /**
     * @inheritdoc
     */
    public function addGlobal(string $name, $value): TemplateEngine
    {
        $this->getTwig()->addGlobal($name, $value);

        return $this;
    }

    /**
     * @inheritdoc
     * @throws \Twig_Error Twig errors
     */
    public function registerPath(string $path, string $namespace = null): TemplateEngine
    {
        $this->getLoader()->addPath($path, $namespace);

        return $this;
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
        $str = $this->getTwig()->render($name, $variables);

        // Debug
        $this->getApp()->getDebug()->getTimeLine()->addActivity($twigActivity->end());

        // Log
        if ($this->getApp()->getServiceContainer()->has(LoggerInterface::class)) {
            $this->getApp()
                 ->getServiceContainer()
                 ->get(LoggerInterface::class)
                 ->debug(sprintf('%s / Rendering of template "%s" done in %1.4fms', __METHOD__,
                                 $name,
                                 $twigActivity->duration() / 1000));
        }

        return $str;
    }

    /**
     * @inheritdoc
     * @throws \Twig_Error Twig errors
     */
    public function hasBlock(string $name, string $blockName): bool
    {
        $template = $this->getTwig()->load($name);

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
        $template = $this->getTwig()->load($name);
        $str = $template->renderBlock($blockName, $variables);

        // Debug
        $this->getApp()->getDebug()->getTimeLine()->addActivity($twigActivity->end());

        // Log
        if ($this->getApp()->getServiceContainer()->has(LoggerInterface::class)) {
            $this->getApp()
                 ->getServiceContainer()
                 ->get(LoggerInterface::class)
                 ->debug(sprintf('%s / Rendering of block "%s" in template "%s" done in %1.3fms',
                                 __METHOD__,
                                 $blockName,
                                 $name,
                                 $twigActivity->duration() / 1000));
        }

        return $str;
    }
}