<?php

namespace App\Repository;

use App\Entity\Stops;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Stops>
 *
 * @method Stops|null find($id, $lockMode = null, $lockVersion = null)
 * @method Stops|null findOneBy(array $criteria, array $orderBy = null)
 * @method Stops[]    findAll()
 * @method Stops[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StopsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stops::class);
    }

    public function save(Stops $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Stops $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findStopById(string $id): ?Stops
    {
        $qb = $this->createQueryBuilder('S');
        $qb->select('S')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->orX(
                        'S.stop_id = :id',
                        'S.parent_station = :id'
                    ),
                    'S.location_type = 1'
                )
            )
            ->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }

//    /**
//     * @return Stops[] Returns an array of Stops objects
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

//    public function findOneBySomeField($value): ?Stops
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
