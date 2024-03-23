<?php

namespace App\Repository;

use App\Entity\TraficApplicationPeriods;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TraficApplicationPeriods>
 *
 * @method TraficApplicationPeriods|null find($id, $lockMode = null, $lockVersion = null)
 * @method TraficApplicationPeriods|null findOneBy(array $criteria, array $orderBy = null)
 * @method TraficApplicationPeriods[]    findAll()
 * @method TraficApplicationPeriods[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TraficApplicationPeriodsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TraficApplicationPeriods::class);
    }

//    /**
//     * @return TraficApplicationPeriods[] Returns an array of TraficApplicationPeriods objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?TraficApplicationPeriods
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
