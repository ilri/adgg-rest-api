<?php

namespace App\Extensions;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\AnimalEvent;
use Doctrine\ORM\QueryBuilder;

/**
 * Class CalvingEventsExtension
 * @package App\Extensions
 * @see https://api-platform.com/docs/core/extensions/
 * @see https://symfonycasts.com/screencast/api-platform-security/query-extension
 */
class CalvingEventsExtension implements QueryCollectionExtensionInterface
{

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        if ($operationName !== 'calving_events') {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->andWhere(sprintf('%s.eventType = :eventType', $rootAlias))
            ->setParameter('eventType', AnimalEvent::EVENT_TYPE_CALVING)
        ;
    }
}
