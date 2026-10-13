<?php

namespace App\DataFixtures;

use App\Entity\HormoneEntity;
use App\Entity\ReferenceRangeEntity;
use App\Factory\EventFactory;
use App\Factory\LabResultFactory;
use App\Factory\MeasurementFactory;
use App\Factory\PostFactory;
use App\Factory\UserFactory;
use App\Service\DefaultDataSeeder;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

use function Zenstruck\Foundry\faker;
use function Zenstruck\Foundry\Persistence\repository;

class AppFixtures extends Fixture
{
    public function __construct(
        private DefaultDataSeeder $seeder,
    ) {}

    public function load(ObjectManager $manager): void
    {
        /**
         * Seed db with predefined values
         */
        $this->seeder->seed();

        /**
         * Create mock users
         */
        UserFactory::createSequence(
            function () {
                foreach (range(1, 5) as $i) {
                    yield ['email' => "user_{$i}@example.org"];
                }
            }
        );

        /**
         * Create mock posts (published)
         * 
         * Post without labresults
         * Post with published labresults
         * Post with unpublished labresults
         * Post with multiple published labresults
         * Post with multiple unpublished labresults
         */
        foreach (UserFactory::all() as $user) {
            PostFactory::new()
                ->published()
                ->sequence([
                    [],
                    ['labResults' => LabResultFactory::new()->published()->many(1)->reuse($user)->create()],
                    ['labResults' => LabResultFactory::new()->many(1)->reuse($user)->create()],
                    ['labResults' => LabResultFactory::new()->published()->many(3)->reuse($user)->create()],
                    ['labResults' => LabResultFactory::new()->many(3)->reuse($user)->create()],
                ])
                ->reuse($user)
                ->create();
        }

        /**
         * Create mock posts (unpublished)
         * 
         * Post without labresults
         * Post with unpublished labresults
         * Post with published labresults
         * Post with multiple unpublished labresults
         * Post with multiple published labresults
         */
        foreach (UserFactory::all() as $user) {
            PostFactory::new()
                ->sequence([
                    [],
                    ['labResults' => LabResultFactory::new()->published()->many(1)->reuse($user)->create()],
                    ['labResults' => LabResultFactory::new()->many(1)->reuse($user)->create()],
                    ['labResults' => LabResultFactory::new()->published()->many(3)->reuse($user)->create()],
                    ['labResults' => LabResultFactory::new()->many(3)->reuse($user)->create()],
                ])
                ->reuse($user)
                ->create();
        }

        /**
         * Create mock events
         * 
         * Event with criticality
         * Event with early notification
         * Event with reapeat interval
         * Event with criticality, early notification and repeat interval
         */
        foreach (UserFactory::all() as $user) {
            EventFactory::new()
                ->isCritical()
                ->reuse($user)
                ->create();

            EventFactory::new()
                ->withEarlyNotification(7)
                ->reuse($user)
                ->create();

            EventFactory::new()
                ->withRepeatInterval(30 * 3)
                ->reuse($user)
                ->create();

            EventFactory::new()
                ->isCritical()
                ->withEarlyNotification(7)
                ->withRepeatInterval(15)
                ->reuse($user)
                ->create();
        }

        /** 
         * Create mock measurements and attach to labResults
         */
        $hormoneLH = repository(HormoneEntity::class)->findOneBy(['commonName' => 'LH']);
        $hormoneFSH = repository(HormoneEntity::class)->findOneBy(['commonName' => 'FSH']);
        $hormonePRL = repository(HormoneEntity::class)->findOneBy(['commonName' => 'PRL']);
        $hormoneE2 = repository(HormoneEntity::class)->findOneBy(['commonName' => 'E2']);
        $hormoneProg = repository(HormoneEntity::class)->findOneBy(['commonName' => 'Prog']);
        $hormoneT = repository(HormoneEntity::class)->findOneBy(['commonName' => 'T']);

        $wpathReferenceRangeE2 = repository(ReferenceRangeEntity::class)->findOneBy(['name' => 'WPATH SOC8', 'hormone' => $hormoneE2]);
        $wpathReferenceRangeT = repository(ReferenceRangeEntity::class)->findOneBy(['name' => 'WPATH SOC8', 'hormone' => $hormoneT]);

        foreach (LabResultFactory::all() as $labResult) {
            MeasurementFactory::new()
                ->sequence([
                    ['hormone' => $hormoneLH, 'unit' => $hormoneLH->getDefaultUnit(), 'value' => self::floatToDouble(faker()->randomFloat(1, 0.1, 100))],
                    ['hormone' => $hormoneFSH, 'unit' => $hormoneFSH->getDefaultUnit(), 'value' => self::floatToDouble(faker()->randomFloat(1, 0.1, 200))],
                    ['hormone' => $hormonePRL, 'unit' => $hormonePRL->getDefaultUnit(), 'value' => self::floatToDouble(faker()->randomFloat(1, 0.1, 80))],
                    ['hormone' => $hormoneE2, 'unit' => $hormoneE2->getDefaultUnit(), 'value' => self::floatToDouble(faker()->randomFloat(0, 30, 2500)), 'referenceRange' => $wpathReferenceRangeE2],
                    ['hormone' => $hormoneProg, 'unit' => $hormoneProg->getDefaultUnit(), 'value' => self::floatToDouble(faker()->randomFloat(2, 0.1, 60))],
                    ['hormone' => $hormoneT, 'unit' => $hormoneT->getDefaultUnit(), 'value' => self::floatToDouble(faker()->randomFloat(2, 0.1, 40)), 'referenceRange' => $wpathReferenceRangeT],
                ])
                ->reuse($labResult->getUser())
                ->create(['labResult' => $labResult]);
        }
    }

    private static function floatToDouble(float $num): string
    {
        return number_format($num, 2, '.', '');
    }
}
