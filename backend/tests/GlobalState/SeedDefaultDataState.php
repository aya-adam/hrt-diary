<?php

namespace App\Tests\GlobalState;

use App\Service\DefaultDataSeeder;

final class SeedDefaultDataState
{
    public function __construct(
        private readonly DefaultDataSeeder $seeder,
    ) {}

    public function __invoke(): void
    {
        $this->seeder->seed();
    }
}
