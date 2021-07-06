<?php

namespace App\Repository;

use App\Entity\AnimalEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\{
    NonUniqueResultException,
    NoResultException,
    QueryBuilder
};
use Doctrine\Persistence\ManagerRegistry;

/**
 * @see https://symfonycasts.com/screencast/symfony-doctrine/more-queries
 * @see https://symfonycasts.com/screencast/symfony4-doctrine/repository
 */
class AnimalEventRepository extends ServiceEntityRepository
{
    /**
     * AnimalEventRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnimalEvent::class);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return QueryBuilder
     */
    private function addCalvingEventQueryBuilder(QueryBuilder $queryBuilder): QueryBuilder
    {
        return $queryBuilder
            ->andWhere('a.eventType = :eventType')
            ->setParameter('eventType', AnimalEvent::EVENT_TYPE_CALVING)
        ;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return QueryBuilder
     */
    private function addMilkingEventQueryBuilder(QueryBuilder $queryBuilder): QueryBuilder
    {
        return $queryBuilder
            ->andWhere('a.eventType = :eventType')
            ->setParameter('eventType', AnimalEvent::EVENT_TYPE_MILKING)
        ;
    }

    /**
     * @return int
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function countAllMilkingEvents(): int
    {
        $queryBuilder = $this->createQueryBuilder('a');

        return $this->addMilkingEventQueryBuilder($queryBuilder)
            ->select('count(a.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @param int $id
     * @return AnimalEvent|null
     * @throws NonUniqueResultException
     */
    public function findOneCalvingEventById(int $id): ?AnimalEvent
    {
        $queryBuilder = $this->createQueryBuilder('a');

        return $this->addCalvingEventQueryBuilder($queryBuilder)
            ->andWhere('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @param int $id
     * @return null|AnimalEvent
     * @throws NonUniqueResultException
     */
    public function findOneMilkingEventById(int $id): ?AnimalEvent
    {
        $queryBuilder = $this->createQueryBuilder('a');

        return $this->addMilkingEventQueryBuilder($queryBuilder)
            ->andWhere('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @param int $id
     * @return AnimalEvent|null
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findLactationForMilkingEvent(int $id): ?AnimalEvent
    {
        $milkingEvent = $this->findOneMilkingEventById($id);

        if ($milkingEvent == null) {
            return null;
        }

        $queryBuilder = $this->createQueryBuilder('a');

        return $this->addCalvingEventQueryBuilder($queryBuilder)
            ->andWhere('a.id = :id')
            ->setParameter('id', $milkingEvent->getLactationId())
            ->getQuery()
            ->getSingleResult()
        ;
    }

    /**
     * @return QueryBuilder
     */
    public function findOrphanedMilkingEvents($offset = 0, $pageSize = 100)
    {
        $queryBuilder = $this->createQueryBuilder('a');

        return $this->addMilkingEventQueryBuilder($queryBuilder)
            ->andWhere('a.lactationId IS NULL')
            ->setFirstResult($offset)
            ->setMaxResults($pageSize)
        ;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countOrphanedMilkingEvents(){
        $queryBuilder = $this->createQueryBuilder('a');

        return $this->addMilkingEventQueryBuilder($queryBuilder)
            ->andWhere('a.lactationId IS NULL')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
