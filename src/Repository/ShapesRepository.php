<?php

namespace App\Repository;

use App\Entity\Shapes;
use App\Entity\Routes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Shapes>
 *
 * @method Shapes|null find($id, $lockMode = null, $lockVersion = null)
 * @method Shapes|null findOneBy(array $criteria, array $orderBy = null)
 * @method Shapes[]    findAll()
 * @method Shapes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShapesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Shapes::class);
    }

    public function save(Shapes $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Shapes $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Get the shape geometry as GeoJSON for a specific shape ID using SQL
     */
    public function getLineAsGeoJsonSql(string $routeId, bool $details = true): ?array
    {
        $sql = 'SELECT shape_id, route_id, ST_AsGeoJSON(geometry) as geojson_geometry FROM shapes WHERE route_id = :routeId';
        $params = ['routeId' => $routeId];
        
        $connection = $this->getEntityManager()->getConnection();
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery($params);
        $rows = $result->fetchAllAssociative();

        if (empty($rows)) {
            return null;
        }

        $features = [];
        foreach ($rows as $shape) {
            if (!empty($shape['geojson_geometry'])) {
                $geometry = json_decode($shape['geojson_geometry'], true);

                $routeData = null;
                if (!empty($shape['route_id'])) {
                    $routeEntity = $this->getEntityManager()->getRepository(Routes::class)->find($shape['route_id']);
                    if ($routeEntity) {
                        $routeData = $routeEntity->getRoute();
                    }
                }
                
                $features[] = [
                    'type' => 'Feature',
                    'properties' => $details ? [
                        'shape_id' => $shape['shape_id'],
                        'route_id'  => $shape['route_id'],
                        'route'    => $routeData,
                    ] : [
                        'shape_id' => $shape['shape_id'],
                        'route_id'  => $shape['route_id'],
                    ],
                    'geometry' => $geometry,
                ];
            }
        }

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }

    /**
     * Get the shape geometry as GeoJSON for a specific shape ID using SQL
     */
    public function getLinesAsGeoJsonSql(array $shapesId, bool $details = true): ?array
    {
        $shapesId = array_values(array_filter($shapesId, fn($v) => $v !== null && $v !== ''));
        if (empty($shapesId)) {
            return null;
        }

        $placeholders = implode(',', array_fill(0, count($shapesId), '?'));
        $sql = "SELECT shape_id, route_id, ST_AsGeoJSON(geometry) as geojson_geometry FROM shapes WHERE route_id IN ($placeholders)";

        $connection = $this->getEntityManager()->getConnection();
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery($shapesId);
        $rows = $result->fetchAllAssociative();

        if (empty($rows)) {
            return null;
        }

        $features = [];
        foreach ($rows as $shape) {
            if (!empty($shape['geojson_geometry'])) {
                $geometry = json_decode($shape['geojson_geometry'], true);

                $routeData = null;
                if (!empty($shape['route_id'])) {
                    $routeEntity = $this->getEntityManager()->getRepository(Routes::class)->find($shape['route_id']);
                    if ($routeEntity) {
                        $routeData = $routeEntity->getRoute();
                    }
                }
                
                $features[] = [
                    'type' => 'Feature',
                    'properties' => $details ? [
                        'shape_id' => $shape['shape_id'],
                        'route_id'  => $shape['route_id'],
                        'route'    => $routeData,
                    ] : [
                        'shape_id' => $shape['shape_id'],
                        'route_id'  => $shape['route_id'],
                    ],
                    'geometry' => $geometry,
                ];
            }
        }

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }

    //    /**
    //     * @return Shapes[] Returns an array of Shapes objects
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

    //    public function findOneBySomeField($value): ?Shapes
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}