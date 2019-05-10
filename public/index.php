<?php

use App\Kernel;
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;

use \FOS\HttpCache\SymfonyCache\KernelDispatcher;
use \FOS\HttpCache\ProxyClient\Symfony;

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

$cache = false;
if (\array_key_exists('APP_CACHE', $_SERVER)) {
    $cache = $_SERVER['APP_CACHE'];
}

$env = $_SERVER['APP_ENV'];
$debug = (bool) $_SERVER['APP_DEBUG'];
$kernel = new Kernel($env, $debug, $cache);

if ($cache) {
    // Preparing the ProxyClient as described on https://foshttpcache.readthedocs.io/en/latest/proxy-clients.html#kerneldispatcher-for-single-server-installations
    $dispatcher = new KernelDispatcher($kernel);
    $symfony = new Symfony($dispatcher);

    // Retrieve the EventDispatchingHttpCache/HttpKernelInterface
    $kernel = $kernel->getHttpCache();
}

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
