<?php

namespace App\Repository;

use App\Entity\FixGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method FixGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method FixGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method FixGroup[]    findAll()
 * @method FixGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FixGroupRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, FixGroup::class);
    }

}
