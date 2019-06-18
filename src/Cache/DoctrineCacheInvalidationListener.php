<?php
/**
 * Author: Herman Slatman
 * Date: 2019-05-24
 * Time: 16:22
 */

namespace App\Cache;


use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameResolver;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameResolverInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Entity\Fix;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use FOS\HttpCacheBundle\CacheManager;

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

    public function __construct(CacheManager $manager, RouteNameResolver $resolver)
    {
        $this->manager = $manager;
        $this->resolver = $resolver;
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
        // TODO: we need to look into pagination of the result sets

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->gatherResourceAndItemClasses($entity, false);
            $this->gatherRelationClasses($em, $entity);
        }
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->gatherResourceAndItemClasses($entity, true);
            $this->gatherRelationClasses($em, $entity);
        }
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->gatherResourceAndItemClasses($entity, true);
            $this->gatherRelationClasses($em, $entity);
        }

        //$all_routes = array_merge($this->collection_classes, $this->item_classes);
        //$unique_classes = array_unique($all_classes);


        dump($this->collection_routes);
        dump($this->item_routes);

//
//        $name = 'TODO';
//        //$this->manager->invalidateRoute($name);
    }

    private function gatherResourceAndItemClasses($entity, bool $purgeItem): void
    {
        try {
            if (\get_class($entity) !== Fix::class) {
                return;
            }
            $this->collection_routes[] = $this->resolver->getRouteName(Fix::class, OperationType::COLLECTION);
            if ($purgeItem) {
                $this->item_routes[] = $this->resolver->getRouteName(Fix::class, OperationType::ITEM);
            }
        } catch (InvalidArgumentException $e) {
            return;
        }
    }

    private function gatherRelationClasses(EntityManagerInterface $em, $entity): void
    {
        if (\get_class($entity) !== Fix::class) {
            return;
        }
//        $associationMappings = $em->getClassMetadata(ClassUtils::getClass($entity))->getAssociationMappings();
//        foreach (array_keys($associationMappings) as $property) {
//            $this->addTagsFor($this->propertyAccessor->getValue($entity, $property));
//        }
    }

}