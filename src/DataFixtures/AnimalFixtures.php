<?php

namespace App\DataFixtures;

use App\Entity\Animal;
use Doctrine\Bundle\FixturesBundle\{
    Fixture,
    FixtureGroupInterface,
};
use Doctrine\Persistence\ObjectManager;

class AnimalFixtures extends Fixture implements FixtureGroupInterface
{
    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager): void
    {
        foreach (range(1, 10) as $value) {
            $animal = new Animal();
            // the animals will be assigned to the following countries
            // a1 -> c1; a2 -> c2; a3 -> c3; a4 -> c1; a5 -> c1;
            // a6 -> c2; a7 -> c3; a8 -> c1; a9 -> c1; a10 -> c2;
            // country 1 has 5 animals
            // country 2 has 3 animals
            // country 3 has 2 animals
            $animal->setCountryId($value % 4 ?: 1);
            $animal->setUuid(uniqid());
            $animal->setAdditionalAttributes([
               '1' => 'test value',
            ]);
            //Properties retrieved by the AdministrativeDivisionsListener
            $animal->setVillageId(1);
            $animal->setRegionId(1);
            $animal->setDistrictId(1);
            $animal->setWardId(1);
            $animal->setLongitude(1);
            $animal->setLatitude(1);
            $manager->persist($animal);
            $this->addReference(sprintf('animal_%s', $value), $animal);
        }

        $manager->flush();
    }

    /**
     * @inheritDoc
     */
    public static function getGroups(): array
    {
        return ['test'];
    }
}
