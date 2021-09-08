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

declare(strict_types=1);

namespace Berlioz\Package\Twig\Extension;

use Berlioz\Core\Asset\Assets;
use Berlioz\Core\Asset\EntryPoints;
use Berlioz\Core\Asset\Manifest;
use Berlioz\Core\Exception\AssetException;
use Twig\Error\Error;
use Twig\Error\RuntimeError;

class AssetRuntimeExtension
{
    const H2PUSH_CACHE_COOKIE = 'h2pushes';
    private array $h2pushCache = [];

    public function __construct(protected Assets $assets)
    {
        // Get cache from cookies
        if (isset($_COOKIE[self::H2PUSH_CACHE_COOKIE]) && is_array($_COOKIE[self::H2PUSH_CACHE_COOKIE])) {
            $this->h2pushCache = array_keys($_COOKIE[self::H2PUSH_CACHE_COOKIE]);
        }
    }


    /**
     * Function asset to get generate asset path.
     *
     * @param string $key
     * @param Manifest|null $manifest
     *
     * @return string
     * @throws Error
     */
    public function asset(string $key, ?Manifest $manifest = null): string
    {
        try {
            if (null === $manifest) {
                if (null === ($manifest = $this->assets->getManifest())) {
                    throw new RuntimeError('No entry points file');
                }
            }

            if (false === $manifest->has($key)) {
                throw new RuntimeError(sprintf('Asset "%s" not found in manifest file', $key));
            }

            return $manifest->get($key);
        } catch (AssetException $exception) {
            throw new RuntimeError('Manifest treatment error', previous: $exception);
        }
    }

    /**
     * Function to get entry points in html.
     *
     * @param string $entry
     * @param string|null $type
     * @param array $options
     * @param EntryPoints|null $entryPointsObj
     *
     * @return string
     * @throws RuntimeError
     */
    public function entryPoints(
        string $entry,
        ?string $type = null,
        array $options = [],
        ?EntryPoints $entryPointsObj = null
    ): string {
        $output = '';

        if (null === $entryPointsObj) {
            if (null === ($entryPointsObj = $this->assets->getEntryPoints())) {
                throw new RuntimeError('No entry points file');
            }
        }

        $entryPoints = $entryPointsObj->get($entry, $type);

        if (null !== $type) {
            $entryPoints = [$type => $entryPoints];
        }

        foreach ($entryPoints as $type => $entryPointsByType) {
            foreach ($entryPointsByType as $entryPoint) {
                $entryPoint = strip_tags($entryPoint);

                // Preload option
                $preloadOptions = [];
                if (isset($options['preload'])) {
                    if (is_array($options['preload'])) {
                        $preloadOptions = $options['preload'];
                    }
                }

                switch ($type) {
                    case 'js':
                        if (isset($options['preload'])) {
                            $entryPoint = $this->preload(
                                $entryPoint,
                                array_merge(['as' => 'script'], $preloadOptions)
                            );
                        }

                        // Defer/Async?
                        $deferOrAsync = ($options['defer'] ?? false) === true ? ' defer' : '';
                        $deferOrAsync .= ($options['async'] ?? false) === true ? ' async' : '';

                        $output .= sprintf(
                                '<script src="%s"%s></script>',
                                strip_tags($entryPoint),
                                $deferOrAsync
                            ) . PHP_EOL;
                        break;
                    case 'css':
                        if (isset($options['preload'])) {
                            $entryPoint = $this->preload(
                                $entryPoint,
                                array_merge(['as' => 'style'], $preloadOptions)
                            );
                        }

                        $output .= sprintf('<link rel="stylesheet" href="%s">', strip_tags($entryPoint)) . PHP_EOL;
                        break;
                }
            }
        }

        return $output;
    }

    /**
     * Function to get entry points list.
     *
     * @param string $entry
     * @param string|null $type
     *
     * @return array
     * @throws RuntimeError
     */
    public function entryPointsList(string $entry, ?string $type = null): array
    {
        if (null === $this->assets->getEntryPoints()) {
            throw new RuntimeError('No entry points file');
        }

        return $this->assets->getEntryPoints()->get($entry, $type);
    }

    /**
     * Function preload to pre loading of request for HTTP 2 protocol.
     *
     * @param string $link
     * @param array $parameters
     *
     * @return string Link
     */
    public function preload(string $link, array $parameters = []): string
    {
        $push = !(!empty($parameters['nopush']) && $parameters['nopush'] == true);

        if (true === $push && in_array(md5($link), $this->h2pushCache)) {
            return $link;
        }

        $header = sprintf('Link: <%s>; rel=preload', $link);

        // as
        if (!empty($parameters['as'])) {
            $header = sprintf('%s; as=%s', $header, $parameters['as']);
        }
        // type
        if (!empty($parameters['type'])) {
            $header = sprintf('%s; type=%s', $header, $parameters['as']);
        }
        // crossorigin
        if (!empty($parameters['crossorigin']) && $parameters['crossorigin'] == true) {
            $header .= '; crossorigin';
        }
        // nopush
        if (!$push) {
            $header .= '; nopush';
        }

        header($header, false);

        // Cache
        if ($push) {
            $this->h2pushCache[] = md5($link);

            setcookie(
                sprintf('%s[%s]', self::H2PUSH_CACHE_COOKIE, md5($link)),
                '1',
                [
                    'expires' => 0,
                    'path' => '/',
                    'domain' => '',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict',
                ]
            );
        }

        return $link;
    }
}