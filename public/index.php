<?php

use App\Kernel;
use App\Cache\Cache;
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpCache\Store;


require dirname(__DIR__).'/config/bootstrap.php';

if ($_SERVER['APP_DEBUG']) {
    umask(0000);

    Debug::enable();
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? $_ENV['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? $_ENV['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts([$trustedHosts]);
}

$debug = (bool) $_SERVER['APP_DEBUG'];
$debug = true;
$kernel = new Kernel($_SERVER['APP_ENV'], $debug);
// Wrap the default Kernel with the CacheKernel one in 'prod' environment
//if ('prod' === $kernel->getEnvironment()) {
//    $kernel = new CacheKernel($kernel);
//}

// NOTE: we're basically mirroring the HttpCache initialization from FrameworkBundle here
$storage = new Store($kernel->getCacheDir(). DIRECTORY_SEPARATOR . 'http_cache');

// NOTE: we're always using the cache now, for development purposes that's OK.
$options = [
    'debug' => true,
    'private_headers' => ['Authorization', 'Cookie']
];
$kernel = new Cache($kernel, $storage, null, $options);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
