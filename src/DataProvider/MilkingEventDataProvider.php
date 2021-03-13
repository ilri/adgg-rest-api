<?php


namespace App\DataProvider;

use App\Entity\AnimalEvent;
use App\Repository\MilkYieldRecordCachedDataRepository;
use ApiPlatform\Core\DataProvider\{
    CollectionDataProviderInterface,
    ContextAwareCollectionDataProviderInterface,
    RestrictedDataProviderInterface
};


class MilkingEventDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    /**
     * @var CollectionDataProviderInterface
     */
    private $collectionDataProvider;

    /**
     * @var MilkYieldRecordCachedDataRepository
     */
    private $repository;

    /**
     * MilkingEventDataProvider constructor.
     * @param CollectionDataProviderInterface $collectionDataProvider
     * @param MilkYieldRecordCachedDataRepository $repository
     */
    public function __construct(CollectionDataProviderInterface $collectionDataProvider, MilkYieldRecordCachedDataRepository $repository)
    {
        $this->collectionDataProvider = $collectionDataProvider;
        $this->repository = $repository;
    }

    /**
     * @inheritDoc
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): iterable
    {
        /** @var AnimalEvent[] $milkingEvents */
        $milkingEvents = $this->collectionDataProvider->getCollection($resourceClass, $operationName, $context);

        foreach ($milkingEvents as $milkingEvent) {
            $milkYieldRecord = $this->repository->getMilkYieldRecord($milkingEvent->getId());
            $milkingEvent->setMilkYieldRecord($milkYieldRecord);
        }

        return $milkingEvents;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return AnimalEvent::class == $resourceClass && 'milking_events' == $operationName;
    }
}