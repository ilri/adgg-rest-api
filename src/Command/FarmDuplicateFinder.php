<?php

namespace App\Command;

use App\Entity\Animal;
use App\Entity\Farm;
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

class FarmDuplicateFinder extends Command
{
    private const PAGE_SIZE = 100;
    private const OUTPUT_DIR = '/output/farm_duplicates/';
    private const OUTPUT_FILE = 'farm_duplicates_log_%s.csv';

    /**
     * @var string
     */
    protected static $defaultName = 'adgg:find-duplicate-farms';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Finds and merges duplicated farm resources.';

    /**
     * @var string
     */
    protected static $defaultHelp = 'Creates a CSV file containing duplicated farm resources, which have been located using name and phone number attributes.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Writer $writer
     */
    private $writer;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * FarmDuplicateFinder constructor.
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
        parent::__construct($name);
    }

    /**
     * Initialises the CSV file used for appending processing results.
     * @throws CannotInsertRecord
     */
    private function createCsv(): Writer
    {
        $header = array(
            'primary_farm_id',
            'primary_farm_name',
            'farm_duplicate_ids',
            'animals_merged_ids',
        );
        $now = new \DateTime();

        try {
            $writer = Writer::createFromPath(
                sprintf(
                    $this->projectDir.self::OUTPUT_DIR.self::OUTPUT_FILE,
                    $now->format('Y_m_d_H_i_s')
                ),
                'w'
            );
        } catch (\Exception $exception) {
            echo(sprintf(
                "Please ensure the following output directory has been created: %s\n",
                $this->projectDir.self::OUTPUT_DIR
            ));
            exit;
        }

        $writer->insertOne($header);

        return $writer;
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
        $this->setHelp(self::$defaultHelp);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title(self::$defaultDescription);

        $farmRepository = $this->em->getRepository(Farm::class);
        $farmPaginator = new Paginator($farmRepository->findDuplicatedFarms(), false);

        $io->progressStart($farmPaginator->count());
        $offset = 0;

        while ($offset < $farmPaginator->count()) {
            $farmPaginator = new Paginator(
                $farmRepository->findDuplicatedFarms($offset, self::PAGE_SIZE), false
            );

            foreach ($farmPaginator as $farmRecord) {
                try {
                    $this->processRecord($farmRecord);
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

        return Command::SUCCESS;
    }

    /**
     * Retrieves duplicates of a given farm record,
     * and calls a function to re-assign animals to the primary
     * farm.
     *
     * @param Farm $farm
     * @return void
     * @throws CannotInsertRecord
     */
    private function processRecord(Farm $farm): void
    {
        $farmRepository = $this->em->getRepository(Farm::class);
        $farmRecords = $farmRepository->findGroupOfDuplicates($farm);

        if (!empty($farmRecords)) {
            $this->reassignFarmAnimals($farmRecords);
        }
    }

    /**
     * Re-assigns all animal records to the primary farm
     * and logs process to the CSV file.
     *
     * @param $farmRecords
     * @throws CannotInsertRecord
     */
    private function reassignFarmAnimals($farmRecords): void
    {
        $sortedFarmRecords = $this->sortFarmRecordsByDate($farmRecords);
        $primaryFarm = $sortedFarmRecords[0];
        $duplicatedFarms = array_filter($sortedFarmRecords, fn($farm) => $farm != $primaryFarm);
        $duplicatedFarmIds = array_map(fn($farm) => $farm->getId(), $duplicatedFarms);
        $reassignedAnimalIds = [];

        foreach ($duplicatedFarms as $farm) {
            $farmId = $farm->getId();
            //Retrieves all animal records associated with a specific farm ID
            $farmAnimals = $this->em->getRepository(Animal::class)->findBy(['farm' => $farmId]);

            //If associated animal records exist, transfers all records to primary farm
            if (!empty($farmAnimals)) {
                foreach ($farmAnimals as $animal) {
                    array_push($reassignedAnimalIds, $animal->getId());
                    $animal->setFarm($primaryFarm);
                }
            }
            $this->em->remove($farm);
        }
        $this->writer->insertOne(
            array(
                $primaryFarm->getId(),
                $primaryFarm->getName(),
                $duplicatedFarmIds ? implode("/", $duplicatedFarmIds) : 'Not found',
                $reassignedAnimalIds ? implode("/", $reassignedAnimalIds) : 'Not found',
            )
        );
    }

    /**
     * Sorts the farm records by their 'createdAt' property,
     * with earlier created records coming first.
     *
     * @param $farmRecords
     * @return array|null
     */
    private function sortFarmRecordsByDate($farmRecords): ?array
    {
        usort(
            $farmRecords,
            fn($a, $b) => ($a->getCreatedAt()->getTimestamp()) - ($b->getCreatedAt()->getTimestamp())
        );

        return $farmRecords;
    }
}
