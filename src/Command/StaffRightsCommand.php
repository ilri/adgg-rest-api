<?php

namespace App\Command;

use App\Entity\{
    MasterList,
    MasterListType,
    TableAttribute,
    User
};
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\{
    CannotInsertRecord,
    Exception as CsvException,
    Reader,
    Writer
};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{
    InputInterface,
    InputOption
};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class StaffRightsCommand extends Command
{
    private const ACTIVITY_TYPE_ID = '728';
    private const STAFF_HASRIGHT_ID = '730';
    private const DATA_SOURCE = '/src/Data/activities_list.csv';
    private const OUTPUT_DIR = '/output/staff_rights/';
    private const OUTPUT_FILE = 'staff_rights.csv';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var string
     */
    protected static $defaultName = 'adgg:get-staff-rights';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Generate a CSV file with staff rights';

    /**
     * StaffRightsCommand constructor.
     * @param EntityManagerInterface $em
     * @param string $projectDir
     * @param string|null $name
     */
    public function __construct(EntityManagerInterface $em, string $projectDir, string $name = null)
    {
        $this->em = $em;
        $this->projectDir = $projectDir;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_REQUIRED,
                'The name of the output CSV file',
                self::OUTPUT_FILE
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Generate a CSV file with staff rights');
        $io->writeln([
            'Fetching data from the database',
            '',
        ]);

        $filename = $input->getOption('output');

        $result = $this->generateResult($io);

       $this->generateOutput($result, $filename);

        $io->success(sprintf('A CSV file %s has been created.', $filename));

        return Command::SUCCESS;
    }

    /**
     * Get item from table core_master_list_type where id is ACTIVITY_TYPE_ID
     * SELECT * FROM core_table_attribute WHERE id = ACTIVITY_TYPE_ID
     *
     * @return MasterListType
     */
    private function getMasterListTypeItemForActivityType(): MasterListType
    {
        $tableAttribute = $this->em->getRepository(TableAttribute::class)
            ->findOneBy(['id' => self::ACTIVITY_TYPE_ID]);

        return $tableAttribute->getListType();
    }

    /**
     * Get all items from the table core_master_list where list_type_id is
     * the MasterListType retrieved from the method getMasterListType
     *
     * SELECT * FROM core_master_list WHERE list_type_id = {master_list_type}
     *
     * @return MasterList[]
     */
    private function getMasterListItemsForActivityType(): array
    {
        $activityType = $this->getMasterListTypeItemForActivityType();

        return $this->em->getRepository(MasterList::class)->findBy(
            [
                'listType' => $activityType,
            ]
        );
    }

    /**
     * @param MasterList[] $masterListEntries
     * @param string $key
     * @return MasterList
     */
    private function getSingleMasterListEntry(array $masterListEntries, string $key): MasterList
    {
        return current(array_filter($masterListEntries, function($item) use ($key) {
            return $item->getValue() == $key;
        }));
    }

    /**
     * @throws CsvException
     */
    private function getActivitiesList(): array
    {
        $reader = Reader::createFromPath($this->projectDir.self::DATA_SOURCE);
        $reader->setHeaderOffset(0);
        $records = $reader->getRecords();
        return iterator_to_array($records);
    }

    /**
     * @param string $key
     * @param array $activitiesList
     * @return array
     */
    private function getActivityValuesFromInputData(string $key, array $activitiesList): array
    {
        $index = array_search($key,array_column($activitiesList, 'activity_code'));
        $record = $activitiesList[$index + 1];
        return [
            'activity_type' => $record['activity_type'],
            'activity_order' => $record['activity_order'],
        ];
    }

    /**
     * @param SymfonyStyle $io
     * @return array
     */
    private function generateResult(SymfonyStyle $io): array
    {
        $users = $this->em->getRepository(User::class)
            ->findAllUsersWithAdditionalAttributeKey(self::ACTIVITY_TYPE_ID)
        ;
        $masterListItems = $this->getMasterListItemsForActivityType();
        try {
            $activitiesList = $this->getActivitiesList();
        } catch (CsvException $e) {
            $io->error($e);
            $io->error('The data input file for activities list could not be read.');
            die;
        }

        $result = [];

        $io->writeln(sprintf('Calculating staff rights for <info>%s</info> users:', count($users)));
        $io->listing($users);
        $io->progressStart(count($users));
        foreach ($users as $user) {
            $additionalAttributes = $user->getAdditionalAttributes();
            $activityTypeKeys = $additionalAttributes[self::ACTIVITY_TYPE_ID];
            $activityTypeKeys = is_string($activityTypeKeys) ? json_decode($activityTypeKeys) : $activityTypeKeys;
            $staffHasRight = $additionalAttributes[self::STAFF_HASRIGHT_ID];
            foreach ($activityTypeKeys as $key) {
                $entry = [];
                $entry[] = $user->getId(); // staff_code
                $masterListItem = $this->getSingleMasterListEntry($masterListItems, $key);
                $entry[] = $masterListItem->getValue(); // activity_code
                $entry[] = $masterListItem->getLabel(); // activity_name
                $activityValues = $this->getActivityValuesFromInputData($key, $activitiesList);
                $entry[] = $activityValues['activity_type']; // activity_type
                $entry[] = $activityValues['activity_order']; // activity_order
                $entry[] = $staffHasRight; // staff_hasright
                $result[] = $entry;
            }
            $io->progressAdvance();
        }
        $io->progressFinish();

        return $result;
    }

    /**
     * @param array $result
     * @param string $filename
     */
    private function generateOutput(array $result, string $filename): void
    {
        $header = [
            'staff_code',
            'activity_code',
            'activity_name',
            'activity_type',
            'activity_order',
            'staff_hasright',
        ];

        //Creating a CSV file
        try {
            $writer = Writer::createFromPath(
                $this->projectDir.self::OUTPUT_DIR.$filename,
                'w'
            );
        } catch (\Exception $exception){
            echo(sprintf(
                "A CSV file could not be generated.\nPlease ensure the following output directory has been created: %s\n",
                $this->projectDir.self::OUTPUT_DIR));
            exit;
        }

        //Inserting header and result
        try {
            $writer->insertOne($header);
            $writer->insertAll($result);
        } catch (\Exception $exception){
            echo("Results could not be written to the CSV file.");
            exit;
        }
    }
}
