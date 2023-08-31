<?php

namespace App\Repository;

use App\Entity\StopTown;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StopTown>
 *
 * @method StopTown|null find($id, $lockMode = null, $lockVersion = null)
 * @method StopTown|null findOneBy(array $criteria, array $orderBy = null)
 * @method StopTown[]    findAll()
 * @method StopTown[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StopTownRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StopTown::class);
    }

    //    /**
//     * @return StopTown[] Returns an array of StopTown objects
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

    //    public function findOneBySomeField($value): ?StopTown
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}