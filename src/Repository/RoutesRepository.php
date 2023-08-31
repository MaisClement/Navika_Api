<?php

namespace App\Repository;

use App\Entity\Routes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Routes>
 *
 * @method Routes|null find($id, $lockMode = null, $lockVersion = null)
 * @method Routes|null findOneBy(array $criteria, array $orderBy = null)
 * @method Routes[]    findAll()
 * @method Routes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoutesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Routes::class);
    }

    public function save(Routes $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Routes $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByShortName(string $query): array
    {
        $qb = $this->createQueryBuilder('r');

        $qb->where($qb->expr()->like('LOWER(r.route_short_name)', ':query'))
            ->setParameter('query', '%' . strtolower($query) . '%');

        return $qb->getQuery()->getResult();
    }

    public function findByLongName(string $query): array
    {
        $qb = $this->createQueryBuilder('s');

        $qb->where($qb->expr()->like('LOWER(s.route_long_name)', ':query'))
            ->setParameter('query', '%' . strtolower($query) . '%');

        return $qb->getQuery()->getResult();
    }

    //    /**
//     * @return Routes[] Returns an array of Routes objects
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

    //    public function findOneBySomeField($value): ?Routes
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}