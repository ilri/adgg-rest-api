<?php

namespace App\Command;

use App\Entity\AnimalEvent;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use League\Csv\{
    CannotInsertRecord,
    Writer
};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


final class LactationFinderCommand extends Command
{
    private const PAGE_SIZE = 100;
    private const OUTPUT_DIR = '/output/lactation_finder/';
    private const OUTPUT_FILE = 'lactation_log_%s.csv';

    /**
     * @var string
     */
    protected static $defaultName = 'adgg:assign-lactation-to-milking-event';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Finds and assigns a lactation to orphaned milking events';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var Writer $writer
     */
    private $writer;

    /**
     * @var int
     */
    private $assignedLactations;

    /**
     * LactationFinderCommand constructor.
     * @param EntityManagerInterface $em
     * @param string $projectDir
     * @param string|null $name
     * @throws CannotInsertRecord
     */
    public function __construct(EntityManagerInterface $em, string $projectDir, string $name = null)
    {
        $this->em = $em;
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        $this->projectDir = $projectDir;
        $this->writer = $this->createCsv();
        $this->assignedLactations = 0;
        parent::__construct($name);
    }

    /**
     * Initialises the CSV file used for appending processing results.
     * @throws CannotInsertRecord
     */
    private function createCsv(): Writer
    {
        $header = [
            'milking_event_id',
            'last_calving_event_id',
            'assigned',
        ];
        $now = new \DateTime();

        $writer = Writer::createFromPath(
            sprintf($this->projectDir.self::OUTPUT_DIR.self::OUTPUT_FILE, $now->format('Y_m_d_H_i_s')),
            'w'
        );

        $writer->insertOne($header);

        return $writer;
    }

    protected function configure()
    {
        $this->setDescription(self::$defaultDescription);
    }

    /**
     * Retrieves and processes all orphaned milking events
     * iteratively, limited by the page size constant.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Find and assign a lactation to orphaned milking events');

        $animalEventRepository = $this->em->getRepository(AnimalEvent::class);
        $paginator = new Paginator($animalEventRepository->findOrphanedMilkingEvents(), false);

        //Process user choice to limit the number of processed orphaned milking events
        $choice = $io->choice(
            'Would you like to limit the number of orphaned milking events processed?',
            ['yes', 'no']
        );
        if ($choice == 'yes'){
            $userLimit = $io->ask(sprintf(
                'What number of orphaned milking events would you like to process? The maximum is %s.',
                $paginator->count()
            ));
        }

        $recordLimit = $userLimit < $paginator->count() ? $userLimit : $paginator->count();
        $offset = 0;

        $io->info(sprintf(
            "%d orphaned milking records have been retrieved from the database and will now be processed.",
            $recordLimit
        ));
        $io->progressStart($recordLimit);

        while ($offset < $recordLimit) {
            $page = new Paginator(
                $animalEventRepository->findOrphanedMilkingEvents(
                    $offset,
                    self::PAGE_SIZE
                ),
                false
            );
            foreach ($page as $record) {
                try {
                    $this->processRecord($record);
                } catch (CannotInsertRecord $e) {
                    $io->error('The record could not be appended to the CSV file.');
                }
                $io->progressAdvance();
            }
            $this->em->flush();
            $this->em->clear();
            $offset += self::PAGE_SIZE;
        }
        $io->progressFinish();
        $io->info('All orphaned milking events have now been processed.');
        $io->info(sprintf('The number of lactations assigned is: %d', $this->assignedLactations));

        return Command::SUCCESS;
    }

    /**
     * Sets the lactation ID on an orphaned milking event record,
     * if a calving event for that milking event exists.
     *
     * Logs the milking event ID, lactation ID and whether the assignment
     * has been successful.
     *
     * @param AnimalEvent $record
     * @throws CannotInsertRecord
     */
    private function processRecord(AnimalEvent $record): void
    {
        $lactationId = $this->retrieveLactationId($record);
        $assigned = 'N';

        if ($lactationId) {
            $record = $record->setLactationId($lactationId);
            $this->em->persist($record);
            $assigned = 'Y';
            $this->assignedLactations += 1;
        }

        $this->writer->insertOne([$record->getId(), $lactationId ?? 'Not found', $assigned]);
    }

    /**
     * Retrieves the most recent calving event for a given milking record
     * and, if present, checks whether it occurred no more than 1000 days
     * prior. Only returns a lactation Id if both these conditions are met.
     *
     *
     * @param AnimalEvent $record
     * @return int|null
     */
    private function retrieveLactationId(AnimalEvent $record): ?int
    {
        $lastCalvingEvent = $this
            ->em
            ->getRepository(AnimalEvent::class)
            ->findLastCalvingEvent($record)
        ;

        if (!$lastCalvingEvent) {
            return null;
        }

        $lastCalvingEventDate = $lastCalvingEvent->getEventDate();
        $milkingEventDate = $record->getEventDate();
        $interval = date_diff($lastCalvingEventDate, $milkingEventDate);

        return $interval->days <= 1000 ? $lastCalvingEvent->getId() : null;
    }
}
