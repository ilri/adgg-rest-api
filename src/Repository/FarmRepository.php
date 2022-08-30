<?php

namespace App\Repository;

use App\Entity\Farm;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @see https://symfonycasts.com/screencast/symfony-doctrine/more-queries
 * @see https://symfonycasts.com/screencast/symfony4-doctrine/repository
 */
class FarmRepository extends ServiceEntityRepository
{
    /**
     * FarmRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Farm::class);
    }

    /**
     * @param $farm
     * @return array|null
     */
    public function findGroupOfDuplicates($farm): ?array
    {
        $queryBuilder = $this->createQueryBuilder('f');
        $farmerName = $farm->getFarmerName(); //get rid of white spaces //lower case
        $farmerPhone = $farm->getPhone(); //get rid of white spaces
        $wardId = $farm->getWardId();

        return $queryBuilder
            ->andWhere('f.farmerName = :farmerName')
            ->setParameter('farmerName', $farmerName)
            ->andWhere('f.phone = :phone')
            ->setParameter('phone', $farmerPhone)
            ->andWhere('f.wardId = :wardId')
            ->setParameter('wardId', $wardId)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findDuplicatedFarms(int $offset = 0, int $pageSize = 100): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('f');

        return $queryBuilder
            ->andWhere('f.farmerName = f.farmerName')
            ->andWhere('f.phone = f.phone')
            ->andWhere('f.wardId = f.wardId')
            ->groupBy('f.name')
            ->having('count(f.name) > 1')
            ->addGroupBy('f.phone')
            ->andHaving('count(f.phone) > 1')
            ->addGroupBy('f.wardId')
            ->andHaving('count(f.wardId) > 1')
            ->setFirstResult($offset)
            ->setMaxResults($pageSize)
        ;
    }
}
