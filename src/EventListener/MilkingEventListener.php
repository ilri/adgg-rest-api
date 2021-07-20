<?php

namespace App\EventListener;

use App\Entity\{
    AnimalEvent,
    MilkYieldRecord
};
use App\Repository\AnimalEventRepository;
use Carbon\Carbon;
use Doctrine\ORM\{
    Mapping as ORM,
    NonUniqueResultException
};
use Doctrine\Persistence\Event\LifecycleEventArgs;

class MilkingEventListener
{
    /**
     * @var AnimalEventRepository
     */
    private $animalEventRepository;

    /**
     * MilkingEventListener constructor.
     * @param AnimalEventRepository $animalEventRepository
     */
    public function __construct(AnimalEventRepository $animalEventRepository)
    {
        $this->animalEventRepository = $animalEventRepository;
    }

    /**
     * @ORM\PostLoad()
     *
     * @param AnimalEvent $milkingEvent
     * @param LifecycleEventArgs $event
     * @return void
     * @throws NonUniqueResultException
     */
    public function postLoad(AnimalEvent $milkingEvent, LifecycleEventArgs $event): void
    {
        if ($milkingEvent->getEventType() !== AnimalEvent::EVENT_TYPE_MILKING) {
            $milkingEvent->setMilkYieldRecord(null);
            return;
        }
        if ($milkingEvent->getLactationId() == null) {
            return;
        }
        $recordCheck = $this->validateMilkingEvent($milkingEvent);
        if (!($recordCheck['cowAgeWithinRange'] && $recordCheck['cowAlive'])) {
            return;
        }

        $eventId = $milkingEvent->getId();
        $calvingEvent = $this->animalEventRepository->findOneCalvingEventById($milkingEvent->getLactationId());
        $dim = $this->getDIMForMilkingEvent($eventId);
        $emy = $this->getEMYForMilkingEvent($eventId);
        $totalMilkRecord = $this->getTotalMilkRecord($milkingEvent);
        $feedback = $this->getFeedbackForFarmer($totalMilkRecord, $emy);
        $farmId = $milkingEvent->getAnimal()->getFarm()->getId();
        $milkYieldRecord = new MilkYieldRecord();
        $milkYieldRecord
            ->setId($eventId)
            ->setCalvingDate($calvingEvent->getEventDate())
            ->setDaysInMilk($dim)
            ->setTotalMilkRecord($totalMilkRecord)
            ->setExpectedMilkYield($emy['EMY'])
            ->setUpperLimit($emy['TU'])
            ->setLowerLimit($emy['TL'])
            ->setFeedback($feedback)
            ->setFarmId($farmId)
            ->setFarmRelocation($recordCheck['farmRelocation'])
        ;

        $milkingEvent->setMilkYieldRecord($milkYieldRecord);
    }

    /**
     * Returns an array containing information on
     * whether the cow is of an appropriate age,
     * alive and has relocated to another farm.
     *
     * @param AnimalEvent $milkingEvent
     * @return array
     */
    private function validateMilkingEvent(AnimalEvent $milkingEvent): array
    {
        $animal = $milkingEvent->getAnimal();
        $exitEvents = $this->retrieveExitEvents($milkingEvent);

        $cowAgeWithinRange = $this->checkCowAgeWithinRange($animal, 8);
        $farmRelocation = $exitEvents && $this->checkFarmRelocation($exitEvents);
        $cowAlive = !$exitEvents || $farmRelocation;

        return [
            'cowAgeWithinRange' => $cowAgeWithinRange,
            'cowAlive' => $cowAlive,
            'farmRelocation' => $farmRelocation,
        ];
    }

    /**
     * Checks whether a cow is within the specified age range.
     *
     * @param $animal
     * @param $maximumAge
     * @return bool
     */
    private function checkCowAgeWithinRange($animal, $maximumAge): bool
    {
        $animalAge = Carbon::now()->diff($animal->getBirthdate())->y;

        return $animalAge < $maximumAge;
    }

    /**
     * Retrieves the animal that is associated with the
     * milking event and returns all of its exit events.
     *
     * @param AnimalEvent $milkingEvent
     * @return array
     */
    private function retrieveExitEvents(AnimalEvent $milkingEvent): array
    {
        $animal = $milkingEvent->getAnimal();

        return $animal
            ->getAnimalEvents()
            ->filter(
                function (AnimalEvent $event) {
                    return $event->getEventType() == AnimalEvent::EVENT_TYPE_EXITS;
                }
            )
            ->getValues();
    }

    /**
     * Iterates through all exit events and checks
     * whether the reasons of disposal indicate
     * a farm relocation rather than death.
     *
     */
    private function checkFarmRelocation($exitEvents): bool
    {
        //indicate farm relocation
        $acceptableReasons = [2, 4, 11, 12, 13];
        $disposalReasons = array_map(
            fn($event) => $event->getAdditionalAttributes()[247],
            $exitEvents
        );

        foreach ($disposalReasons as $reason) {
            if (!in_array($reason, $acceptableReasons)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int $id
     * @return int|null
     * @throws NonUniqueResultException
     */
    private function getDIMForMilkingEvent(int $id): ?int
    {
        $milkingEvent = $this->animalEventRepository->findOneMilkingEventById($id);
        $calvingEvent = $this->animalEventRepository->findOneCalvingEventById($milkingEvent->getLactationId());

        $milkingEventDate = Carbon::parse($milkingEvent->getEventDate()->format('Y-m-d'));
        $calvingEventDate = Carbon::parse($calvingEvent->getEventDate()->format('Y-m-d'));

        return $milkingEventDate->diffInDays($calvingEventDate);
    }

    /**
     * @param int $id
     * @return array
     * @throws NonUniqueResultException
     */
    private function getEMYForMilkingEvent(int $id): array
    {
        $dim = $this->getDIMForMilkingEvent($id);
        $exponent = -0.0017 * $dim;
        $emy = 8.11 * pow($dim, 0.068) * exp($exponent);

        return [
            'EMY' => $emy,
            'TU' => $emy + 2.3,
            'TL' => $emy - 2.3
        ];
    }

    /**
     * @param AnimalEvent $milkingEvent
     * @return float|null
     */
    private function getTotalMilkRecord(AnimalEvent $milkingEvent): ?float
    {
        $additionalAttributes = $milkingEvent->getAdditionalAttributes();
        $morning = $additionalAttributes['59'];
        $evening = $additionalAttributes['61'];
        $midday = $additionalAttributes['68'] ?? 0;

        return $morning + $evening + $midday;
    }

    /**
     * @param float $milkRecord
     * @param array $emy
     * @return string
     */
    private function getFeedbackForFarmer(float $milkRecord, array $emy): string
    {
        if ($milkRecord > $emy['TU']) {
            return 'NOTE';
        } elseif ($milkRecord < $emy['TL']) {
            return 'ALARM';
        } else {
            return '';
        }
    }
}
