<?php
/**
 * Author: Herman Slatman
 * Date: 2019-05-24
 * Time: 16:22
 *
 * NOTE: this file was heavily inspired on the PurgeHttpCacheListener in API Platform:
 * https://github.com/api-platform/core/blob/44a686cd9c047520d809e0c529b368822e9bb948/src/Bridge/Doctrine/EventListener/PurgeHttpCacheListener.php
 *
 */

namespace App\Cache;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameResolver;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameResolverInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\RuntimeException;
use App\Entity\Fix;
use App\Entity\FixGroup;
use App\Entity\FixRelation;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\PersistentCollection;
use FOS\HttpCacheBundle\CacheManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use \Symfony\Component\PropertyAccess\PropertyAccessor;

class DoctrineCacheInvalidationUsingRouteNamesListener
{

    /** @var string[] $collection_routes */
    private $collection_routes = [];

    /** @var string[] $item_routes */
    private $item_routes = [];

    /** @var CacheManager $manager */
    private $manager;

    /** @var RouteNameResolverInterface $resolver */
    private $resolver;

    /** @var PropertyAccessor $property_accessor */
    private $property_accessor;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var string[] $classes
     *
     * Only the classes in this array will be acted upon by the DoctrineCacheInvalidationListener
     */
    private $classes = [
        Fix::class,
        FixGroup::class,
        FixRelation::class,
    ];

    public function __construct(CacheManager $manager, RouteNameResolver $resolver, LoggerInterface $logger)
    {
        $this->manager = $manager;
        $this->resolver = $resolver;
        $this->logger = $logger;
        $this->property_accessor = PropertyAccess::createPropertyAccessor();
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $object = $args->getObject();
        $this->gatherResourceRoutes($object);
        $change_set = $args->getEntityChangeSet();
        $association_mappings = $args->getEntityManager()->getClassMetadata(ClassUtils::getClass($object))->getAssociationMappings();
        foreach ($change_set as $key => $value) {
            if (!isset($association_mappings[$key])) {
                continue;
            }
            $this->addRoutesFor($value[0]);
            $this->addRoutesFor($value[1]);
        }
    }

    public function onFlush(OnFlushEventArgs $args) {
        // TODO: gather the entity types; filter out for the sake of the POC
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        // NOTE: we don't need to purge an item on insertion; only new item created.
        // We don't need to purge the collection when an item is added, because FOSHttpCache already manages that.
        // We do need to purge the collection when an entity is updated or deleted, though.

        // On second thought, perhaps it's better to always purge, because we need to update too when
        // something is handled within a service. Of course we could make this configurable in some way, though...

        // We also should always update collections/items when there are related entities

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            // NOTE: including the entity insertions, because we might be running in a service, not in a request
            // This results in the same cache entry being invalidated twice during a request.
            $this->gatherResourceRoutes($entity, false);
            $this->gatherRelationRoutes($em, $entity);
        }
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->gatherResourceRoutes($entity, true);
            $this->gatherRelationRoutes($em, $entity);
        }
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->gatherResourceRoutes($entity, true);
            $this->gatherRelationRoutes($em, $entity);
        }

    }

    public function postFlush(): void
    {
        if (empty($this->collection_routes)) {
            return;
        }

        $unique_collection_routes = array_unique($this->collection_routes);

        foreach($unique_collection_routes as $route) {
            $this->logger->debug("Invalidating route: {$route}");
            $this->manager->invalidateRoute($route);
        }

        $unique_item_routes = array_unique($this->item_routes);
        foreach ($unique_item_routes as $item) {
            $route = $item['route'];
            $parameters = $item['parameters'];
            $this->logger->debug("Invalidating route: {$route}");
            $this->manager->invalidateRoute($route, $parameters);
        }

        $this->collection_routes = [];
        $this->item_routes = [];

    }

    private function gatherResourceRoutes($entity, $purge_item = false): void
    {

        // TODO: filter doubles? i.e. the ones that are triggerd automatically by symfony/cache already?

        try {
            $class = ClassUtils::getClass($entity);
            if (!\in_array($class, $this->classes, true)) {
                return;
            }

            $route = $this->resolver->getRouteName($class, OperationType::COLLECTION);
            $this->collection_routes[] = $route;

            if ($purge_item) {
                $route = $this->resolver->getRouteName($class, OperationType::ITEM);
                $parameters = ['id' => $entity->getId()]; // TODO: make safer? Through reflexion? Helper functions in API Platform?
                $this->item_routes[] = ['route' => $route, 'parameters' => $parameters];
            }

        } catch (InvalidArgumentException $e) {
            $this->logger->error($e);
            return;
        }
    }

    private function gatherRelationRoutes(EntityManagerInterface $em, $entity): void
    {
        $class = ClassUtils::getClass($entity);
        if (!\in_array($class, $this->classes, true)) {
            return;
        }

        $association_mappings = $em->getClassMetadata($class)->getAssociationMappings();
        foreach (array_keys($association_mappings) as $property) {
            $this->addRoutesFor($this->property_accessor->getValue($entity, $property));
        }
    }

    private function addRoutesFor($value) : void
    {

        if (!$value) {
            return;
        }

        if (!is_iterable($value)) {
            $this->addTagForItem($value);
            return;
        }

        if ($value instanceof PersistentCollection) {
            $value = clone $value;
        }

        foreach ($value as $v) {
            $this->addTagForItem($v);
        }

    }

    private function addTagForItem($value): void
    {
        try {
            $class = ClassUtils::getClass($value);
            if (!\in_array($class, $this->classes, true)) {
                return;
            }

            $route = $this->resolver->getRouteName($class, OperationType::COLLECTION);
            $this->collection_routes[] = $route;

            // TODO: do we need look for item routes?

        } catch (InvalidArgumentException $e) {
            $this->logger->error($e);
            return;
        } catch (RuntimeException $e) {
            $this->logger->error($e);
            return;
        }

    }
}