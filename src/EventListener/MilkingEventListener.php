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

        $eventId = $milkingEvent->getId();

        if (!$this->validateMilkingEvent($milkingEvent)) {
            return;
        };

        $calvingEvent = $this->animalEventRepository->findOneCalvingEventById($milkingEvent->getLactationId());
        $dim = $this->getDIMForMilkingEvent($eventId);
        $emy = $this->getEMYForMilkingEvent($eventId);
        $totalMilkRecord = $this->getTotalMilkRecord($milkingEvent);
        $feedback = $this->getFeedbackForFarmer($totalMilkRecord, $emy);
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
        ;

        $milkingEvent->setMilkYieldRecord($milkYieldRecord);
    }

    /**
     * @param int $id
     * @return int
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

    /**
     * Checks whether a milking event derives from an animal
     * under the age of 8, and without any exit events.
     *
     * @param AnimalEvent $milkingEvent
     * @return bool
     */
    private function validateMilkingEvent(AnimalEvent $milkingEvent): bool
    {
        $animal = $milkingEvent->getAnimal();
        $animalAge = Carbon::now()->diff($animal->getBirthdate())->y;
        $exitEvents = $animal
            ->getAnimalEvents()
            ->filter(function (AnimalEvent $element) {
                return $element->getEventType() == AnimalEvent::EVENT_TYPE_EXITS;
            })
            ->getValues();
        //Cow assumed alive if no exit events are present
        $cowAlive = $exitEvents ? $this->checkCowAlive($exitEvents) : true;

        return $animalAge < 8 && $cowAlive;
    }

    /**
     * Retrieves all disposal reasons from the additional attributes array
     * on an exit event. If any of these do not match the acceptable reasons,
     * cow is assumed to have died.
     *
     * @param $exitEvents
     * @return bool
     */
    private function checkCowAlive($exitEvents): bool
    {
        $acceptableReasons = [13,12,11,4,2];
        $disposalReasons = array_map(fn($event)=>$event->getAdditionalAttributes()[247], $exitEvents);

        foreach($disposalReasons as $reason){
            if(!in_array($reason, $acceptableReasons)){
                return false;
            }
        }
        return true;
    }
}
