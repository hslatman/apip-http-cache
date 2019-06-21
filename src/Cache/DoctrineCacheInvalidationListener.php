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
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\PersistentCollection;
use FOS\HttpCacheBundle\CacheManager;
use Symfony\Component\PropertyAccess\PropertyAccess;

class DoctrineCacheInvalidationListener
{

    /** @var string[] $collection_routes */
    private $collection_routes = [];

    /** @var string[] $item_routes */
    private $item_routes = [];

    /** @var CacheManager $manager */
    private $manager;

    /** @var RouteNameResolverInterface $resolver */
    private $resolver;

    /** @var \Symfony\Component\PropertyAccess\PropertyAccessor $property_accessor */
    private $property_accessor;

    public function __construct(CacheManager $manager, RouteNameResolver $resolver)
    {
        $this->manager = $manager;
        $this->resolver = $resolver;
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
            $this->gatherResourceRoutes($entity);
            $this->gatherRelationRoutes($em, $entity);
        }
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->gatherResourceRoutes($entity);
            $this->gatherRelationRoutes($em, $entity);
        }
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->gatherResourceRoutes($entity);
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
            $this->manager->invalidateRoute($route);
        }

        $this->collection_routes = [];

    }

    private function gatherResourceRoutes($entity): void
    {

        // TODO: filter doubles? i.e. the ones that are triggerd automatically by symfony/cache already?

        try {
            $class = ClassUtils::getClass($entity);
            if ($class !== Fix::class) {
                return;
            }

            $route = $this->resolver->getRouteName($class, OperationType::COLLECTION);
            $this->collection_routes[] = $route;

        } catch (InvalidArgumentException $e) {
            return;
        }
    }

    private function gatherRelationRoutes(EntityManagerInterface $em, $entity): void
    {
        $class = ClassUtils::getClass($entity);
        if ($class !== Fix::class) {
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
            if ($class !== Fix::class) {
                return;
            }

            $route = $this->resolver->getRouteName($class, OperationType::COLLECTION);
            $this->collection_routes[] = $route;

        } catch (InvalidArgumentException $e) {

        } catch (RuntimeException $e) {

        }

    }
}