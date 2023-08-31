<?php

namespace App\Repository;

use App\Entity\StopRoute;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StopRoute>
 *
 * @method StopRoute|null find($id, $lockMode = null, $lockVersion = null)
 * @method StopRoute|null findOneBy(array $criteria, array $orderBy = null)
 * @method StopRoute[]    findAll()
 * @method StopRoute[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StopRouteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StopRoute::class);
    }

    public function save(StopRoute $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(StopRoute $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByQueryName(string $query): array
    {
        $qb = $this->createQueryBuilder('sr');

        $qb->where($qb->expr()->like('LOWER(sr.stop_query_name)', ':query'))
            ->setParameter('query', '%' . strtolower($query) . '%');

        return $qb->getQuery()->getResult();
    }

    public function findByTownName(string $query): array
    {
        $qb = $this->createQueryBuilder('sr');

        $qb->where($qb->expr()->like('LOWER(sr.town_name)', ':query'))
            ->setParameter('query', '%' . strtolower($query) . '%');

        return $qb->getQuery()->getResult();
    }

    public function findByNearbyLocation(float $latitude, float $longitude, float $distance): array
    {
        $qb = $this->createQueryBuilder('l');

        $qb->select('l')
            ->where("STDistanceSphere(POINT(l.stop_lat, l.stop_lon), POINT(:latitude, :longitude)) <= :distance")
            ->setParameter('latitude', $latitude)
            ->setParameter('longitude', $longitude)
            ->setParameter('distance', $distance);

        return $qb->getQuery()->getResult();
    }

    //    /**
//     * @return StopRoute[] Returns an array of StopRoute objects
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

    //    public function findOneBySomeField($value): ?StopRoute
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}