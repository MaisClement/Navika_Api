<?php

namespace App\Repository;

use App\Entity\Maps;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Maps>
 *
 * @method Maps|null find($id, $lockMode = null, $lockVersion = null)
 * @method Maps|null findOneBy(array $criteria, array $orderBy = null)
 * @method Maps[]    findAll()
 * @method Maps[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MapsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Maps::class);
    }

    public function findAllByOrder()
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.number', 'ASC')
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Maps[] Returns an array of Maps objects
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

//    public function findOneBySomeField($value): ?Maps
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
