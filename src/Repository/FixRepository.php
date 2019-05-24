<?php

namespace App\Repository;

use App\Entity\Fix;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Fix|null find($id, $lockMode = null, $lockVersion = null)
 * @method Fix|null findOneBy(array $criteria, array $orderBy = null)
 * @method Fix[]    findAll()
 * @method Fix[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FixRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Fix::class);
    }

}
