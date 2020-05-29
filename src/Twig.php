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

namespace Berlioz\Package\Twig;

use Berlioz\Core\Core;
use Berlioz\Core\CoreAwareInterface;
use Berlioz\Core\CoreAwareTrait;
use Berlioz\Core\Debug;
use Berlioz\Core\Exception\BerliozException;
use Berlioz\ServiceContainer\Exception\ContainerException;
use Berlioz\ServiceContainer\Exception\InstantiatorException;
use Exception;
use Throwable;
use Twig\Environment;
use Twig\Error\Error;
use Twig\Error\LoaderError;
use Twig\Extension\DebugExtension;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;

/**
 * Class Twig.
 *
 * @package Berlioz\Package\Twig
 */
class Twig implements CoreAwareInterface
{
    use CoreAwareTrait;

    /** @var ChainLoader */
    private $loader;
    /** @var Environment */
    private $twig;

    /**
     * Twig constructor.
     *
     * @param Core $core Berlioz Core
     * @param array $paths Twig paths
     * @param array $options Twig options
     * @param string[] $extensions Twig extensions classes
     * @param array $globals Globals variables
     *
     * @throws BerliozException
     * @throws ContainerException
     * @throws InstantiatorException
     * @throws LoaderError
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
        $this->loader = new ChainLoader();
        $this->loader->addLoader(
            $fileLoader = new FilesystemLoader([], $this->getCore()->getDirectories()->getAppDir())
        );
        $this->twig = new Environment($this->loader, $options);

        // Debug?
        if ($options['debug'] ?? false) {
            $this->getEnvironment()->addExtension(new DebugExtension());
        }

        // Paths
        foreach ($paths as $namespace => $path) {
            $fileLoader->addPath($path, $namespace);
        }

        // Add extensions
        $extensions = array_unique($extensions);
        foreach ($extensions as $extension) {
            if (!is_object($extension)) {
                $extension =
                    $this
                        ->getCore()
                        ->getServiceContainer()
                        ->getInstantiator()
                        ->newInstanceOf(
                            $extension,
                            [
                                'templating' => $this,
                                'twigLoader' => $this->loader,
                                'twig' => $this->twig,
                            ]
                        );
            }

            $this->getEnvironment()->addExtension($extension);
        }

        // Add globals
        foreach ($globals as $name => $value) {
            $this->getCore()
                ->getServiceContainer()
                ->getInstantiator()
                ->invokeMethod(
                    $this->getEnvironment(),
                    'addGlobal',
                    ['name' => $name, 'value' => $value]
                );
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
     * @return ChainLoader
     */
    public function getLoader(): ChainLoader
    {
        return $this->loader;
    }

    /**
     * Get Twig environment.
     *
     * @return Environment
     */
    public function getEnvironment(): Environment
    {
        return $this->twig;
    }

    /**
     * Render template.
     *
     * @param string $name Template name
     * @param array $variables
     *
     * @return string
     * @throws BerliozException
     * @throws Error
     */
    public function render(string $name, array $variables = []): string
    {
        $twigActivity =
            (new Debug\Activity('Twig rendering'))
                ->start()
                ->setDescription(sprintf('Rendering of template "%s"', $name));

        // Twig rendering
        try {
            return $this->getEnvironment()->render($name, $variables);
        } catch (Error $e) {
            throw $e;
        } catch (Exception $e) {
            throw new Error('An error occurred during rendering', -1, null, $e);
        } catch (Throwable $e) {
            throw new BerliozException('An error occurred during rendering', 0, $e);
        } finally {
            // Debug
            $this->getCore()->getDebug()->getTimeLine()->addActivity($twigActivity->end());
        }
    }

    /**
     * Has block in template?
     *
     * @param string $name Template name
     * @param string $blockName Block name
     *
     * @return bool
     * @throws Error
     */
    public function hasBlock(string $name, string $blockName): bool
    {
        $template = $this->getEnvironment()->load($name);

        return $template->hasBlock($blockName);
    }

    /**
     * Render block of template.
     *
     * @param string $name Template name
     * @param string $blockName Block name
     * @param array $variables
     *
     * @return string
     * @throws BerliozException
     * @throws Error
     */
    public function renderBlock(string $name, string $blockName, array $variables = []): string
    {
        $twigActivity =
            (new Debug\Activity('Twig block rendering'))
                ->start()
                ->setDescription(
                    sprintf(
                        'Rendering of block "%s" in template "%s"',
                        $blockName,
                        $name
                    )
                );

        // Twig rendering
        try {
            $template = $this->getEnvironment()->load($name);
            $str = $template->renderBlock($blockName, $variables);

            return $str;
        } catch (Error $e) {
            throw $e;
        } catch (Exception $e) {
            throw new Error('An error occurred during rendering', -1, null, $e);
        } catch (Throwable $e) {
            throw new BerliozException('An error occurred during rendering', 0, $e);
        } finally {
            // Debug
            $this->getCore()->getDebug()->getTimeLine()->addActivity($twigActivity->end());
        }
    }
}