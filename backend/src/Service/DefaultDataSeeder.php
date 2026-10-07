<?php

namespace App\Service;

use App\Entity\HormoneEntity;
use App\Entity\QuantityTypeEntity;
use App\Entity\ReferenceRangeEntity;
use App\Entity\UnitEntity;
use App\Repository\HormoneRepository;
use App\Repository\QuantityTypeRepository;
use App\Repository\ReferenceRangeRepository;
use App\Repository\UnitRepository;
use Doctrine\ORM\EntityManagerInterface;

final class DefaultDataSeeder
{
    private const array DATA = [
        'Biological Activity Concentration' => [
            [
                'symbol' => 'IU/L',
                'label' => 'International units per litre',
                'hormones' => [
                    ['name' => 'Luteinizing Hormone', 'commonName' => 'LH'],
                    ['name' => 'Follicle Stimulating Hormone', 'commonName' => 'FSH'],
                ]
            ],
        ],
        'Molar Concentration' => [
            [
                'symbol' => 'mol/L',
                'label' => 'Moles per litre',
                'hormones' => []
            ],
            [
                'symbol' => 'mmol/L',
                'label' => 'Millimoles per litre',
                'hormones' => []
            ],
            [
                'symbol' => 'μmol/L',
                'label' => 'Micromoles per litre',
                'hormones' => []
            ],
            [
                'symbol' => 'nmol/L',
                'label' => 'Nanomoles per litre',
                'hormones' => [
                    ['name' => 'Sex Hormone-Binding Globulin', 'commonName' => 'SHBG'],
                    ['name' => 'Progesterone', 'commonName' => 'Prog'],
                    ['name' => 'Testosterone', 'commonName' => 'T', 'referenceRange' => [
                        ['name' => 'WPATH SOC8', 'lowerLimit' => '0', 'upperLimit' => '1.73', 'notes' => 'WPATH = World Professional Association for Transgender Healtz / SOC8 = Standards of Care 8th Version'],
                    ]],
                    ['name' => 'Dihydrotestosterone', 'commonName' => 'DHT'],
                ]
            ],
            [
                'symbol' => 'pmol/L',
                'label' => 'Picomoles per litre',
                'hormones' => [
                    ['name' => 'Estradiol', 'commonName' => 'E2', 'referenceRange' => [
                        ['name' => 'WPATH SOC8', 'lowerLimit' => '367.12', 'upperLimit' => '734.25', 'notes' => 'WPATH = World Professional Association for Transgender Healtz / SOC8 = Standards of Care 8th Version'],
                    ]],
                ]
            ],
        ],
        'Mass Concentration' => [
            [
                'symbol' => 'g/L',
                'label' => 'Grams per litre',
                'hormones' => []
            ],
            [
                'symbol' => 'mg/mL',
                'label' => 'Milligrams per millilitre',
                'hormones' => []
            ],
            [
                'symbol' => 'mg/dL',
                'label' => 'Milligrams per decilitre',
                'hormones' => []
            ],
            [
                'symbol' => 'mg/L',
                'label' => 'Milligrams per litre',
                'hormones' => []
            ],
            [
                'symbol' => 'μg/mL',
                'label' => 'Micrograms per millilitre',
                'hormones' => [
                    ['name' => 'Prolactin', 'commonName' => 'PRL'],
                ]
            ],
            [
                'symbol' => 'μg/dL',
                'label' => 'Micrograms per decilitre',
                'hormones' => []
            ],
            [
                'symbol' => 'μg/L',
                'label' => 'Micrograms per litre',
                'hormones' => []
            ],
            [
                'symbol' => 'ng/mL',
                'label' => 'Nanograms per millilitre',
                'hormones' => []
            ],
            [
                'symbol' => 'ng/dL',
                'label' => 'Nanograms per decilitre',
                'hormones' => []
            ],
            [
                'symbol' => 'ng/L',
                'label' => 'Nanograms per litre',
                'hormones' => []
            ],
            [
                'symbol' => 'pg/mL',
                'label' => 'Picograms per millilitre',
                'hormones' => []
            ],
            [
                'symbol' => 'pg/dL',
                'label' => 'Picograms per decilitre',
                'hormones' => []
            ],
            [
                'symbol' => 'pg/L',
                'label' => 'Picograms per litre',
                'hormones' => []
            ],
        ],
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly HormoneRepository $hormoneRepository,
        private readonly QuantityTypeRepository $quantityTypeRepository,
        private readonly UnitRepository $unitRepository,
        private readonly ReferenceRangeRepository $referenceRangeRepository,
    ) {}

    /**
     * Seed db with predefined values
     * 
     * The following entities are pre-populated:
     * - Hormones
     * - QuantityTypes
     * - ReferenceRanges
     * - Units
     */
    public function seed(): void
    {
        $this->assertTableExists(HormoneEntity::class);
        $this->assertTableExists(QuantityTypeEntity::class);
        $this->assertTableExists(UnitEntity::class);

        $this->em->wrapInTransaction(function () {
            foreach (self::DATA as $quantityTypeLabel => $units) {
                $quantityType = $this->seedQuantityType($quantityTypeLabel);

                foreach ($units as $unitData) {
                    $unit = $this->seedUnit($quantityType, $unitData['symbol'], $unitData['label']);

                    foreach ($unitData['hormones'] as $hormoneData) {
                        $hormone = $this->seedHormone($unit, $hormoneData['name'], $hormoneData['commonName']);

                        if (isset($hormoneData['referenceRange']) && !empty($hormoneData['referenceRange'])) {
                            foreach ($hormoneData['referenceRange'] as $referenceRangeData) {
                                $this->seedReferenceRange($hormone, $referenceRangeData['name'], $referenceRangeData['lowerLimit'], $referenceRangeData['upperLimit'], $referenceRangeData['notes']);
                            }
                        }
                    }
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

    private function seedHormone(UnitEntity $unit, string $name, ?string $commonName): HormoneEntity
    {
        $existing = $this->hormoneRepository->findOneBy(['name' => $name]);

        if ($existing !== null) {
            return $existing;
        }

        $hormone = new HormoneEntity();
        $hormone->setName($name);
        $hormone->setDefaultUnit($unit);
        if (!empty($commonName)) $hormone->setCommonName($commonName);
        $this->em->persist($hormone);

        return $hormone;
    }

    private function seedQuantityType(string $label): QuantityTypeEntity
    {
        $existing = $this->quantityTypeRepository->findOneBy(['label' => $label]);

        if ($existing !== null) {
            return $existing;
        }

        $quantityType = new QuantityTypeEntity();
        $quantityType->setLabel($label);
        $this->em->persist($quantityType);

        return $quantityType;
    }

    private function seedUnit(QuantityTypeEntity $quantityType, string $symbol, string $label): UnitEntity
    {
        $existing = $this->unitRepository->findOneBy(['symbol' => $symbol]);

        if ($existing !== null) {
            return $existing;
        }

        $unit = new UnitEntity();
        $unit->setSymbol($symbol);
        $unit->setLabel($label);
        $unit->setQuantityType($quantityType);
        $this->em->persist($unit);

        return $unit;
    }

    private function seedReferenceRange(HormoneEntity $hormone, string $name, string $lowerLimit, string $upperLimit, ?string $notes): ReferenceRangeEntity
    {
        $existing = $this->referenceRangeRepository->findOneBy(['hormone' => $hormone, 'name' => $name]);

        if ($existing !== null) {
            return $existing;
        }

        $referenceRange = new ReferenceRangeEntity();
        $referenceRange->setHormone($hormone);
        $referenceRange->setUnit($hormone->getDefaultUnit());
        $referenceRange->setName($name);
        $referenceRange->setLowerLimit($lowerLimit);
        $referenceRange->setUpperLimit($upperLimit);
        if (!empty($notes)) $referenceRange->setNotes($notes);
        $this->em->persist($referenceRange);

        return $referenceRange;
    }
}
