<?php

namespace App\Tests\Entity;

use App\Entity\Animal;
use App\Entity\AnimalEvent;
use App\Entity\MilkYieldRecord;
use Carbon\Carbon;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class AnimalUnitTest extends TestCase
{
    private static $animal;
    private static $milkingEvent1;
    private static $milkingEvent2;
    private static $milkingEventCollection;
    private static $calvingEvent1;
    private static $calvingEvent2;
    private static $animalEvent;
    private static $milkYieldRecord;

    public function setUp(): void
    {
        self::$milkingEvent1 = new AnimalEvent();
        self::$milkingEvent1->setEventType(AnimalEvent::EVENT_TYPE_MILKING);
        self::$milkingEvent1->setCountryId(1);
        self::$milkingEvent1->setEventDate(new \DateTime('2021-01-01'));
        self::$milkingEvent1->setLactationId(1);
        self::$milkingEvent1->setAdditionalAttributes(
            [
                '59' => '4', //Milk Am (Litres)
                '61' => '4', //Milk Pm (Litres)
                '68' => '4', //Milk Mid Day (Litres)
            ]
        );
        self::$milkingEvent2 = new AnimalEvent();
        self::$milkingEvent2->setEventType(AnimalEvent::EVENT_TYPE_MILKING);
        self::$milkingEvent2->setCountryId(2);
        self::$milkingEvent2->setEventDate(new \DateTime('2020-01-01'));
        self::$milkingEvent2->setLactationId(2);
        self::$milkingEvent2->setAdditionalAttributes(
            [
                '59' => '5', //Milk Am (Litres)
                '61' => '5', //Milk Pm (Litres)
                '68' => '5', //Milk Mid Day (Litres)
            ]
        );
        self::$milkingEventCollection = new ArrayCollection([self::$milkingEvent1, self::$milkingEvent2]);

        self::$calvingEvent1 = new AnimalEvent();
        self::$calvingEvent1->setEventType(AnimalEvent::EVENT_TYPE_CALVING);
        self::$calvingEvent1->setCountryId(1);
        self::$calvingEvent1->setEventDate(new \DateTime('2021-01-01'));
        self::$calvingEvent2 = new AnimalEvent();
        self::$calvingEvent2->setEventType(AnimalEvent::EVENT_TYPE_CALVING);
        self::$calvingEvent2->setCountryId(2);
        self::$calvingEvent2->setEventDate(new \DateTime('2020-01-01'));

        self::$milkYieldRecord = new MilkYieldRecord();
        self::$milkYieldRecord->setId(1);
        self::$milkYieldRecord->setCalvingDate(self::$calvingEvent1->getEventDate());
        self::$milkYieldRecord->setDaysInMilk(100);
        self::$milkYieldRecord->setTotalMilkRecord(100);
        self::$milkYieldRecord->setExpectedMilkYield(50);
        self::$milkYieldRecord->setUpperLimit(70);
        self::$milkYieldRecord->setLowerLimit(30);
        self::$milkYieldRecord->setFeedback('');

        self::$animal = new Animal();
        self::$animal->getAnimalEvents()[] = self::$milkingEvent1;
        self::$milkingEvent1->setAnimal(self::$animal);
        self::$animal->getAnimalEvents()[] = self::$milkingEvent2;
        self::$milkingEvent2->setAnimal(self::$animal);
        self::$animal->getAnimalEvents()[] = self::$calvingEvent1;
        self::$calvingEvent1->setAnimal(self::$animal);
        self::$animal->getAnimalEvents()[] = self::$calvingEvent2;
        self::$calvingEvent2->setAnimal(self::$animal);

        self::$milkingEvent1->setMilkYieldRecord(self::$milkYieldRecord);
        self::$milkingEvent2->setMilkYieldRecord(self::$milkYieldRecord);
    }

    public function testAddAnimalEvent()
    {
        self::$animalEvent = new AnimalEvent();
        self::$animal->addAnimalEvent(self::$animalEvent);

        $this->assertContains(self::$animalEvent, self::$animal->getAnimalEvents());
    }

    public function testRemoveAnimalEvent()
    {
        self::$animal->removeAnimalEvent(self::$milkingEvent1);

        $this->assertNotContains(self::$milkingEvent1, self::$animal->getAnimalEvents());
    }

    public function testGetLastCalving()
    {
        $this->assertEquals(self::$calvingEvent1, self::$animal->getLastCalving());
    }

    public function testGetCalvingInterval()
    {
        $days = Carbon::now()->diff(self::$calvingEvent1->getEventDate())->days;
        $alarm = $days > 365;

        $expectedCalvingInterval = [
            'days_since_last_calving' => $days,
            'alarm' => $alarm,
        ];
        $this->assertEquals($expectedCalvingInterval, self::$animal->getCalvingInterval());
    }

    public function testGetMilkingEvents()
    {
        $this->assertEquals(self::$milkingEventCollection, self::$animal->getMilkingEvents());
    }

    public function testGetLastMilkingEvent()
    {
        $this->assertEquals(self::$milkingEvent1, self::$animal->getLastMilkingEvent());
    }

    public function testGetAverageMilkYield()
    {
        $func = function ($event) {
            return $event->getMilkYieldRecord()->getTotalMilkRecord();
        };
        $milkYieldRecords = array_map($func, self::$milkingEventCollection->toArray());
        $averageMilkYield = array_sum($milkYieldRecords) / count(self::$milkingEventCollection);

        $this->assertEquals($averageMilkYield, self::$animal->getAverageMilkYield());
    }
}
