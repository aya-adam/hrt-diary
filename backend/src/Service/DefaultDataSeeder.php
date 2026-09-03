<?php

namespace App\Service;

use App\Entity\QuantityType;
use App\Entity\Unit;
use App\Repository\QuantityTypeRepository;
use App\Repository\UnitRepository;
use Doctrine\ORM\EntityManagerInterface;

final class DefaultDataSeeder
{
    private const array DATA = [
        'Biological Activity Concentration' => [
            ['symbol' => 'IU/L', 'label' => 'International units per litre'],
        ],
        'Molar Concentration' => [
            ['symbol' => 'mol/L', 'label' => 'Moles per litre'],
            ['symbol' => 'mmol/L', 'label' => 'Millimoles per litre'],
            ['symbol' => 'μmol/L', 'label' => 'Micromoles per litre'],
            ['symbol' => 'nmol/L', 'label' => 'Nanomoles per litre'],
            ['symbol' => 'pmol/L', 'label' => 'Picomoles per litre'],
        ],
        'Mass Concentration' => [
            ['symbol' => 'g/L', 'label' => 'Grams per litre'],
            ['symbol' => 'mg/mL', 'label' => 'Milligrams per millilitre'],
            ['symbol' => 'mg/dL', 'label' => 'Milligrams per decilitre'],
            ['symbol' => 'mg/L', 'label' => 'Milligrams per litre'],
            ['symbol' => 'μg/mL', 'label' => 'Micrograms per millilitre'],
            ['symbol' => 'μg/dL', 'label' => 'Micrograms per decilitre'],
            ['symbol' => 'μg/L', 'label' => 'Micrograms per litre'],
            ['symbol' => 'ng/mL', 'label' => 'Nanograms per millilitre'],
            ['symbol' => 'ng/dL', 'label' => 'Nanograms per decilitre'],
            ['symbol' => 'ng/L', 'label' => 'Nanograms per litre'],
            ['symbol' => 'pg/mL', 'label' => 'Picograms per millilitre'],
            ['symbol' => 'pg/dL', 'label' => 'Picograms per decilitre'],
            ['symbol' => 'pg/L', 'label' => 'Picograms per litre'],
        ],
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly QuantityTypeRepository $quantityTypeRepository,
        private readonly UnitRepository $unitRepository,
    ) {}

    public function seed(): void
    {
        $this->assertTableExists(QuantityType::class);
        $this->assertTableExists(Unit::class);

        $this->em->wrapInTransaction(function () {
            foreach (self::DATA as $quantityTypeLabel => $units) {
                $quantityType = $this->seedQuantityType($quantityTypeLabel);

                foreach ($units as $unitData) {
                    $this->seedUnit($quantityType, $unitData['symbol'], $unitData['label']);
                }
            }
        });
    }

    private function assertTableExists(string $entityClass): void
    {
        $tableName = $this->em->getClassMetadata($entityClass)->getTableName();
        $schemaManager = $this->em->getConnection()->createSchemaManager();

        if (!$schemaManager->tableExists($tableName)) {
            throw new \RuntimeException(sprintf(
                'Table "%s" (for %s) does not exist. Run migrations before seeding.',
                $tableName,
                $entityClass
            ));
        }
    }

    private function seedQuantityType(string $label): QuantityType
    {
        $existing = $this->quantityTypeRepository->findOneBy(['label' => $label]);

        if ($existing !== null) {
            return $existing;
        }

        $quantityType = new QuantityType();
        $quantityType->setLabel($label);
        $this->em->persist($quantityType);

        return $quantityType;
    }

    private function seedUnit(QuantityType $quantityType, string $symbol, string $label): Unit
    {
        $existing = $this->unitRepository->findOneBy(['symbol' => $symbol]);

        if ($existing !== null) {
            return $existing;
        }

        $unit = new Unit();
        $unit->setSymbol($symbol);
        $unit->setLabel($label);
        $unit->setQuantityType($quantityType);
        $this->em->persist($unit);

        return $unit;
    }
}
