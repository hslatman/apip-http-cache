<?php
/**
 * Author: Herman Slatman
 * Date: 2019-05-07
 * Time: 20:42
 */

namespace App\Cache;

use ApiPlatform\Core\HttpCache\PurgerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class Purger implements PurgerInterface
{
//    public function purge(array $iris)
//    {
//        // TODO: Implement purge() method.
//    }

//    private $clients;
//
//    /**
//     * @param ClientInterface[] $clients
//     */
//    public function __construct(array $clients)
//    {
//        $this->clients = $clients;
//    }
//    public function __construct()
//    {
//        dd('here');
//    }
//


    /** @var KernelInterface $kernel */
    private $kernel;

    /** @var EventDispatcherInterface $dispatcher */
    private $dispatcher;

    public function __construct(KernelInterface $kernel, EventDispatcherInterface $dispatcher)
    {
        $this->kernel = $kernel;
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function purge(array $iris)
    {

        if (!$iris) {
            return;
        }

        // Create the regex to purge all tags in just one request
        $parts = array_map(function ($iri) {
            return sprintf('(^|\,)%s($|\,)', preg_quote($iri));
        }, $iris);

        $regex = \count($parts) > 1 ? sprintf('(%s)', implode(')|(', $parts)) : array_shift($parts);

//        foreach ($this->clients as $client) {
//            $client->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => $regex]]);
//        }

        // TODO: purge the FosHttpCache
        // Is the key correct, we're using?
        // TODO: add option to refresh the cache with new contents?

        // TODO: integrate with the FosHttpCacheBundle, CacheManager, CacheInvalidator.


        //$event = new PurgeCacheEvent();
        //$this->dispatcher->dispatch($event::PURGE, $event);

        // NOTES:
        // The way I look at it know, is as follows:
        //
        // 1) The default PurgerInterface from API Platform invalidates based on $iris of the objects. This means
        // that the IRIs are mapped to (a) certain key(s), which is then used to purge the cache in Varnish (by default).
        // 2) The PurgeListener in FosHttpCache listens for events, if I'm correct. On a purgeEvent, the handlePurge
        // function will be called, resulting in the request/key/url to be purged to be determined (the current request)
        // and then deleted from the file cache.
        //
        // Proposals for solution:
        //
        // 1) Don't use the PurgeListener from FosHttpCache, implement the custom Purger to take
        // into account the $iris, map those to the right key, and directly affect the $store? Problem is that
        // we don't have direct access to the $store in the Purger.
        // 2) Use the PurgeListener from FosHttpCache (directly, or subclass it), and make the custom Purger
        // trigger the right function in PurgeListener. This requires a request that is similar to the request
        // that was made when storing the cache item for the $iris. We should probably fake the $request to
        // look like the one that a normal API request would look like. I think subclassing the PurgeListener with
        // a custom one, injecting that in the Cache, then reacting to that, is the way to go.

// NOTE: below code comes from the FosHttpCache PurgeListener, which purges data after a PURGE request
//        if ($store->purge($request->getUri())) {
//            $response->setStatusCode(200, 'Purged');
//            $response->setStatusCode(200, 'Not found');
//        } else {
//        }
//        $event->setResponse($response);

//        /**
//         * Purges data for the given URL.
//         *
//         * @param string $url A URL
//         *
//         * @return bool true if the URL exists and has been purged, false otherwise
//         */
//        public function purge($url);

//        /**
//         * Invalidates all cache entries that match the request.
//         */
//        public function invalidate(Request $request);

//        /**
//         * Purges data for the given URL.
//         *
//         * This method purges both the HTTP and the HTTPS version of the cache entry.
//         *
//         * @param string $url A URL
//         *
//         * @return bool true if the URL exists with either HTTP or HTTPS scheme and has been purged, false otherwise
//         */
//        public function purge($url)
//    {
//        $http = preg_replace('#^https:#', 'http:', $url);
//        $https = preg_replace('#^http:#', 'https:', $url);
//
//        $purgedHttp = $this->doPurge($http);
//        $purgedHttps = $this->doPurge($https);
//
//        return $purgedHttp || $purgedHttps;

//        /**
//         * Purges data for the given URL.
//         *
//         * @param string $url A URL
//         *
//         * @return bool true if the URL exists and has been purged, false otherwise
//         */
//        private function doPurge($url)
//    {
//        $key = $this->getCacheKey(Request::create($url));
//        if (isset($this->locks[$key])) {
//            flock($this->locks[$key], LOCK_UN);
//            fclose($this->locks[$key]);
//            unset($this->locks[$key]);
//        }
//
//        if (file_exists($path = $this->getPath($key))) {
//            unlink($path);
//
//            return true;
//        }
//
//        return false;
//    }
//    }

    }

}