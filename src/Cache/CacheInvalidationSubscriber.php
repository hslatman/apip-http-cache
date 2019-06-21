<?php
/**
 * Author: Herman Slatman
 * Date: 2019-05-10
 * Time: 14:19
 */

namespace App\Cache;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameResolver;
use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Bug;

use Doctrine\Common\Util\ClassUtils;
use FOS\HttpCacheBundle\CacheManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CacheInvalidationSubscriber implements EventSubscriberInterface
{

    /** @var CacheManager $manager */
    private $manager;

    /** @var RouteNameResolver $resolver */
    private $resolver;

    public function __construct(CacheManager $manager, RouteNameResolver $resolver)
    {
        $this->manager = $manager;
        $this->resolver = $resolver;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['triggerCacheInvalidation', EventPriorities::PRE_WRITE],
        ];
    }

    public function triggerCacheInvalidation(GetResponseForControllerResultEvent $event) : void {
        $entity = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        // NOTE: we're only enabling this method for triggering the cache for the Bug entity
        if (!$entity instanceof Bug) {
            return;
        }

        // NOTE: other mutating methods to include?
        $mutating_methods = [Request::METHOD_POST, Request::METHOD_PUT, Request::METHOD_DELETE, Request::METHOD_PATCH];
        //safe_methods = [Request::METHOD_GET, Request::METHOD_HEAD];

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        $route_parts = explode('_', $route);

        // We only need to perform cache invalidation on collection type routes when an item is being mutated.
        // The collection type routes are handled by HttpCache itself, as well as the item retrieval operation.
        $is_item_route = $route_parts[\count($route_parts)-1] === 'item';
        if (!$is_item_route) {
            return;
        }

        // NOTE: we only need to purge the cache in case the request has a mutating method
        $should_purge_collection = \in_array($method, $mutating_methods, true);
        if (!$should_purge_collection) {
            return;
        }

        $class = ClassUtils::getClass($entity);
        $collection_route = $this->resolver->getRouteName($class, OperationType::COLLECTION);

        // We only need to invalidate the collection route by default in API Platform; the other default operations are managed by HttpCache itself
        $this->manager->invalidateRoute($collection_route);

        // NOTE: we could implement all of the invalidations in this class, including the ones that are managed by FOSHttpCacheBundle
        // by having the fos_http_cache.invalidation.enabled set to true. This could be disabled, after which we would be in full control over our
        // cache invalidation process.

        // NOTE: we could also look into refreshing the cache using this listener or a different listener.

        // NOTE: the flush() function is called automatically on kernel.terminate.event (https://foshttpcachebundle.readthedocs.io/en/latest/reference/cache-manager.html#flush)
        //$this->manager->flush();
    }
}