<?php

namespace App\EventListener;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class AdministrativeDivisionsListener
{
    /**
     *
     * Retrieves missing AdministrativeDivisionsTrait properties,
     * as well as longitude and latitude,
     * from the animal resource related to a given animal event.
     *
     * @ORM\PrePersist()
     *
     * @param $entity
     * @param LifecycleEventArgs $event
     */
    public function prePersist($entity, LifecycleEventArgs $event): void
    {
        $entityTraits = class_uses($entity);

        if (!in_array('App\Entity\Traits\AdministrativeDivisionsTrait', $entityTraits)) {
            return;
        }

        $animal = $entity->getAnimal();

        $villageId = $entity->getVillageId() ?? $animal->getVillageId();
        $regionId = $entity->getRegionId() ?? $animal->getRegionId();
        $districtId = $entity->getDistrictId() ?? $animal->getDistrictId();
        $wardId = $entity->getWardId() ?? $animal->getWardId();
        $latitude = $entity->getLatitude() ?? $animal->getLatitude();
        $longitude = $entity->getLongitude() ?? $animal->getLongitude();

        $entity->setVillageId($villageId);
        $entity->setRegionId($regionId);
        $entity->setDistrictId($districtId);
        $entity->setWardId($wardId);
        $entity->setLatitude($latitude);
        $entity->setLongitude($longitude);
    }
}
