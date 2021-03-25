<?php

namespace App\DataFixtures;

use App\Entity\AnimalEvent;
use Doctrine\Bundle\FixturesBundle\{
    Fixture,
    FixtureGroupInterface
};
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AnimalEventFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        foreach (range(1, 10) as $value) {
            // create 10 calving events
            $calvingEvent = new AnimalEvent();
            $calvingEvent->setEventType(1);
            $calvingEvent->setEventDate(new \DateTime());
            $calvingEvent->setCountryId(rand(1, 3));
            $calvingEvent->setUuid(uniqid());
            $calvingEvent->setAnimal($this->getReference(sprintf('animal_%s', $value)));
            $manager->persist($calvingEvent);
            $this->addReference(sprintf('calving_event_%s', $value), $calvingEvent);

            // create 10 milking events
            $milkingEvent = new AnimalEvent();
            $milkingEvent->setEventType(2);
            $milkingEvent->setEventDate(new \DateTime());
            $milkingEvent->setLactationId($value);
            $milkingEvent->setCountryId(rand(1, 3));
            $milkingEvent->setUuid(uniqid());
            $milkingEvent->setAnimal($this->getReference(sprintf('animal_%s', $value)));
            $milkingEvent->setAdditionalAttributes([
                '59' => rand(3, 6),
                '61' => rand(3, 6),
                '68' => rand(2, 4),
            ]);
            $manager->persist($milkingEvent);
            $this->addReference(sprintf('milking_event_%s', $value), $milkingEvent);

            // create 10 other animal events
            $animalEvent = new AnimalEvent();
            $animalEvent->setEventType(rand(3, 16));
            $animalEvent->setEventDate(new \DateTime());
            $animalEvent->setCountryId(rand(1, 3));
            $animalEvent->setUuid(uniqid());
            $animalEvent->setAnimal($this->getReference(sprintf('animal_%s', $value)));
            $manager->persist($animalEvent);
            $this->addReference(sprintf('animal_event_%s', $value), $animalEvent);
        }

        $manager->flush();
    }

    /**
     * @inheritDoc
     */
    public function getDependencies()
    {
        return [
            AnimalFixtures::class,
        ];
    }

    /**
     * @inheritDoc
     */
    public static function getGroups(): array
    {
        return ['test'];
    }
}
