<?php

namespace App\Repository;

use App\Entity\RouteDetails;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RouteDetails>
 *
 * @method RouteDetails|null find($id, $lockMode = null, $lockVersion = null)
 * @method RouteDetails|null findOneBy(array $criteria, array $orderBy = null)
 * @method RouteDetails[]    findAll()
 * @method RouteDetails[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RouteDetailsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RouteDetails::class);
    }

//    /**
//     * @return RouteDetails[] Returns an array of RouteDetails objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?RouteDetails
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
