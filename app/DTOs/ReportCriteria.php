<?php

namespace App\DTOs;

use Carbon\CarbonInterface;

class ReportCriteria
{
    public function __construct(
        public readonly CarbonInterface $from,
        public readonly CarbonInterface $to,
    ) {}
}
