<?php

namespace App;

use App\Cache\Cache;
use FOS\HttpCache\SymfonyCache\HttpCacheAware;
use FOS\HttpCache\SymfonyCache\HttpCacheProvider;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Toflar\Psr6HttpCacheStore\Psr6Store;

class Kernel extends BaseKernel implements HttpCacheProvider
{

    use HttpCacheAware;

    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function __construct(string $environment, bool $debug, bool $cache)
    {
        parent::__construct($environment, $debug);

        if ($cache) {
            // NOTE: we're configuring the cache here; we don't have access to the Symfony configuration yet, though.
            $options = [
                'debug' => $debug, // NOTE: the debug option enables the X-Symfony-Cache header for tracking cache hits more narrowly
                //'private_headers' => ['Authorization', 'Cookie']
            ];

            $cache_directory = $this->getCacheDir();
            $cache_tags_header = Cache::TAGS_HEADER;
            $store = new Psr6Store([
                'cache_directory' => $cache_directory, // Psr6Store configures http_cache directory below this
                'cache_tags_header' => $cache_tags_header
            ]);

            // TODO: look into further configuration of the Psr6Store; other cache adapters available?

            $cache = new Cache($this, $store, null, $options);

            // NOTE: we're setting up the Kernel to use the Event Dispatch approach to cache invalidation
            $this->setHttpCache($cache);
        }

    }

    public function registerBundles(): iterable
    {
        $contents = require $this->getProjectDir().'/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->addResource(new FileResource($this->getProjectDir().'/config/bundles.php'));
        $container->setParameter('container.dumper.inline_class_loader', true);
        $confDir = $this->getProjectDir().'/config';

        $loader->load($confDir.'/{packages}/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{packages}/'.$this->environment.'/**/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}_'.$this->environment.self::CONFIG_EXTS, 'glob');
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $confDir = $this->getProjectDir().'/config';

        $routes->import($confDir.'/{routes}/'.$this->environment.'/**/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}'.self::CONFIG_EXTS, '/', 'glob');
    }
}
