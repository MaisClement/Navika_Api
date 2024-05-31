<?php

namespace App\Repository;

use App\Entity\TraficLinks;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TraficLinks>
 *
 * @method TraficLinks|null find($id, $lockMode = null, $lockVersion = null)
 * @method TraficLinks|null findOneBy(array $criteria, array $orderBy = null)
 * @method TraficLinks[]    findAll()
 * @method TraficLinks[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TraficLinksRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TraficLinks::class);
    }

//    /**
//     * @return TraficLinks[] Returns an array of TraficLinks objects
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

//    public function findOneBySomeField($value): ?TraficLinks
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
