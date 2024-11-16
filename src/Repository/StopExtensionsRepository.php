<?php

namespace App\Repository;

use App\Entity\StopExtensions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StopExtensions>
 *
 * @method StopExtensions|null find($id, $lockMode = null, $lockVersion = null)
 * @method StopExtensions|null findOneBy(array $criteria, array $orderBy = null)
 * @method StopExtensions[]    findAll()
 * @method StopExtensions[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StopExtensionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StopExtensions::class);
    }

//    /**
//     * @return StopExtensions[] Returns an array of StopExtensions objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?StopExtensions
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
