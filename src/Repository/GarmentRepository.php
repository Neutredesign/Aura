<?php

namespace App\Repository;

use App\Entity\Garment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Garment>
 */
class GarmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Garment::class);
    }

// src/Repository/GarmentRepository.php

    public function findByUserOrdered($user): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.user = :u')
            ->setParameter('u', $user)
            ->orderBy('g.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
