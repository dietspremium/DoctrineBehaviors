<?php

namespace App\Command;

use App\Entity\Medication;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportMedicationFromCsvCommand extends Command
{
    protected static $defaultName = 'app:import-medication';

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Imports medications from a CSV file')
            ->addArgument('file', InputArgument::OPTIONAL, 'Path to the CSV file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = $input->getArgument('file');

        if (!$file) {
            // Default to the fixture file if no argument provided
            $file = __DIR__ . '/../../../Fixtures/data/medications.csv';
        }

        if (!file_exists($file)) {
            $io->error(sprintf('File not found: %s', $file));
            return Command::FAILURE;
        }

        $io->title('Importing Medications from ' . $file);

        if (($handle = fopen($file, 'r')) !== false) {
            $header = fgetcsv($handle); // Read header row

            // Map CSV headers to setters
            // CSV Header: name_en,name_de,name_pl,brand,company,dosage_strength_en,form_en,active,country

            $count = 0;
            $batchSize = 20;

            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) < count($header)) {
                    continue; // Skip invalid rows
                }

                $row = array_combine($header, $data);

                $medication = new Medication();
                $medication->setNameEn($row['name_en'] ?? null);
                $medication->setNameDe($row['name_de'] ?? null);
                $medication->setNamePl($row['name_pl'] ?? null);
                $medication->setBrand($row['brand'] ?? null);
                $medication->setCompany($row['company'] ?? null);
                $medication->setDosageStrengthEn($row['dosage_strength_en'] ?? null);
                $medication->setFormEn($row['form_en'] ?? null);
                $medication->setActive((bool) ($row['active'] ?? true));
                $medication->setCountry($row['country'] ?? null);

                $this->entityManager->persist($medication);
                $count++;

                if (($count % $batchSize) === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
            }

            fclose($handle);

            $this->entityManager->flush(); // Flush remaining
            $this->entityManager->clear();

            $io->success(sprintf('Imported %d medications.', $count));

            return Command::SUCCESS;
        }

        $io->error('Could not open file.');
        return Command::FAILURE;
    }
}
