<?php

namespace Knp\DoctrineBehaviors\Tests\Functional;

use App\Command\ImportMedicationFromCsvCommand;
use App\Entity\Medication;
use App\Entity\MedicationCategory;
use Knp\DoctrineBehaviors\Tests\AbstractBehaviorTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ImportMedicationTest extends AbstractBehaviorTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure schema is created
        $this->entityManager->createQuery('DELETE FROM App\Entity\Medication')->execute();
    }

    protected function provideCustomConfigs(): array
    {
        return [
            __DIR__ . '/../config/config_test_with_app_entity.php',
        ];
    }

    public function testImportMedications(): void
    {
        // 1. Configure the command
        $command = new ImportMedicationFromCsvCommand($this->entityManager);
        $commandTester = new CommandTester($command);

        // 2. Execute command with the fixture file
        $csvPath = __DIR__ . '/../Fixtures/data/medications.csv';
        $commandTester->execute(['file' => $csvPath]);

        // 3. Verify output
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Importing Medications', $output);
        $this->assertStringContainsString('Imported', $output);

        // 4. Verify Database Content
        $repository = $this->entityManager->getRepository(Medication::class);
        $allMedications = $repository->findAll();

        // We expect at least 25 medications from the CSV
        $this->assertGreaterThanOrEqual(25, count($allMedications));

        // 5. Verify specific record (e.g., Ibuprofen)
        $ibuprofen = $repository->findOneBy(['name_en' => 'Ibuprofen 200mg']);
        $this->assertNotNull($ibuprofen, 'Ibuprofen should be in the database');
        $this->assertSame('Pfizer', $ibuprofen->getCompany());
        $this->assertTrue($ibuprofen->getActive());
        $this->assertSame('200mg', $ibuprofen->getDosageStrengthEn());

        // 6. Verify another record (e.g., Aspirin from Germany)
        $aspirin = $repository->findOneBy(['name_en' => 'Aspirin 81mg']);
        $this->assertNotNull($aspirin);
        $this->assertSame('Germany', $aspirin->getCountry());
    }

    protected function getEntities(): array
    {
        return [
            Medication::class,
            MedicationCategory::class,
        ];
    }
}
