<?php

namespace App\Tests\Story;

use App\Factory\LabResultFactory;
use App\Factory\MeasurementFactory;
use App\Factory\UserFactory;
use Zenstruck\Foundry\Story;

final class MeasurementsStory extends Story
{
    public const int NUM_OF_USERS = 5;
    public const int NUM_OF_LABRESULTS_PER_USER = 3;
    public const int NUM_OF_MEASUREMENTS_PER_LABRESULT = 5;

    public function build(): void
    {
        UserFactory::createMany(self::NUM_OF_USERS);

        foreach (UserFactory::all() as $user) {
            LabResultFactory::createMany(self::NUM_OF_LABRESULTS_PER_USER, ['user' => $user]);
        }

        foreach (LabResultFactory::all() as $labResult) {
            MeasurementFactory::createMany(self::NUM_OF_MEASUREMENTS_PER_LABRESULT, ['user' => $labResult->getUser(), 'labResult' => $labResult]);
        }
    }
}
