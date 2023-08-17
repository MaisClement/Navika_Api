<?php

namespace App\Repository;

use App\Entity\Stations;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Stations>
 *
 * @method Stations|null find($id, $lockMode = null, $lockVersion = null)
 * @method Stations|null findOneBy(array $criteria, array $orderBy = null)
 * @method Stations[]    findAll()
 * @method Stations[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StationsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stations::class);
    }

    public function save(Stations $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Stations $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByNearbyLocation(float $latitude, float $longitude, float $distance): array
    {
        $qb = $this->createQueryBuilder('l');

        $qb->select('l')
            ->where("STDistanceSphere(POINT(l.station_lat, l.station_lon), POINT(:latitude, :longitude)) <= :distance")
            ->setParameter('latitude', $latitude)
            ->setParameter('longitude', $longitude)
            ->setParameter('distance', $distance);

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Stations[] Returns an array of Stations objects
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

//    public function findOneBySomeField($value): ?Stations
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
