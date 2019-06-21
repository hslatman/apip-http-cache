<?php

namespace App\Repository;

use App\Entity\FixRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method FixRelation|null find($id, $lockMode = null, $lockVersion = null)
 * @method FixRelation|null findOneBy(array $criteria, array $orderBy = null)
 * @method FixRelation[]    findAll()
 * @method FixRelation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FixRelationRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, FixRelation::class);
    }

}
