<?php
/**
 * Author: Herman Slatman
 * Date: 2019-05-24
 * Time: 16:22
 */

namespace App\Cache;


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
    /** @var string[] $collection_classes */
    private $collection_classes = [];

    /** @var string[] $item_classes */
    private $item_classes = [];

    /** @var CacheManager $manager */
    private $manager;

    public function __construct(CacheManager $manager)
    {
        $this->manager = $manager;
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

        //        if (\in_array(Fix::class, $this->collection_classes, true)) {
//            dump($this->collection_classes);
//            dd($this->item_classes);
//        }
//
//        $name = 'TODO';
//        //$this->manager->invalidateRoute($name);
    }

    private function gatherResourceAndItemClasses($entity, bool $purgeItem): void
    {
        try {
            $class = \get_class($entity);
            // TODO: create the right route to purge.
            $this->collection_classes[] = $class;
            if ($purgeItem) {
                // TODO: create the right route to purge.
                $this->item_classes[] = $class;
            }
        } catch (InvalidArgumentException $e) {
            return;
        }
    }

    private function gatherRelationClasses(EntityManagerInterface $em, $entity): void
    {
//        $associationMappings = $em->getClassMetadata(ClassUtils::getClass($entity))->getAssociationMappings();
//        foreach (array_keys($associationMappings) as $property) {
//            $this->addTagsFor($this->propertyAccessor->getValue($entity, $property));
//        }
    }

}