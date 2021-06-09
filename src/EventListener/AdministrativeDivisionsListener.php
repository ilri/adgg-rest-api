<?php

namespace App\EventListener;

use App\Entity\AnimalEvent;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class AdministrativeDivisionsListener
{
    /**
     *
     * If no AdministrativeDivisionTrait properties are provided in a milking
     * event POST request, the values of these are retrieved from the related
     * animal resource.
     *
     * @ORM\PrePersist()
     *
     * @param AnimalEvent $entity
     * @param LifecycleEventArgs $event
     */
    public function prePersist(AnimalEvent $entity, LifecycleEventArgs $event): void
    {
        $entityTraits = class_uses($entity);

        if (!in_array('App\Entity\Traits\AdministrativeDivisionsTrait', $entityTraits)
            || $entity->getEventType() !== AnimalEvent::EVENT_TYPE_MILKING
        ) {
            return;
        }

        $animal = $entity->getAnimal();

        if(!($entity->getRegionId()
            ||$entity->getDistrictId()
            ||$entity->getWardId()
            ||$entity->getVillageId()
        )){
            $entity->setRegionId($animal->getRegionId());
            $entity->setDistrictId($animal->getDistrictId());
            $entity->setWardId($animal->getWardId());
            $entity->setVillageId($animal->getVillageId());
            $entity->setLatitude($animal->getLatitude());
            $entity->setLongitude($animal->getLongitude());
        }
    }
}
