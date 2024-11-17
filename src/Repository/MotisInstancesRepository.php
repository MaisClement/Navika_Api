<?php

namespace App\Repository;

use App\Entity\MotisInstances;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MotisInstances>
 *
 * @method MotisInstances|null find($id, $lockMode = null, $lockVersion = null)
 * @method MotisInstances|null findOneBy(array $criteria, array $orderBy = null)
 * @method MotisInstances[]    findAll()
 * @method MotisInstances[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MotisInstancesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MotisInstances::class);
    }

//    /**
//     * @return MotisInstances[] Returns an array of MotisInstances objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?MotisInstances
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
