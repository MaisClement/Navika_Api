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

    /**
     * Instance vers laquelle router les requêtes.
     */
    public function findActive(): ?MotisInstances
    {
        return $this->findOneBy(['active' => true]);
    }

    /**
     * Instance de réserve : celle qui recevra le prochain jeu de données.
     *
     * On prend la plus anciennement vérifiée pour alterner naturellement entre
     * les instances au fil des déploiements.
     */
    public function findStandby(): ?MotisInstances
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.active = false')
            ->orderBy('m.checked_at', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Instances utilisables pour servir du trafic, l'active d'abord.
     *
     * @return MotisInstances[]
     */
    public function findServable(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.state = :running')
            ->andWhere('m.healthy = true')
            ->setParameter('running', MotisInstances::STATE_RUNNING)
            ->orderBy('m.active', 'DESC')
            ->addOrderBy('m.checked_at', 'DESC')
            ->getQuery()
            ->getResult();
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
