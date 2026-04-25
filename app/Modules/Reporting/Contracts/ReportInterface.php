<?php

namespace App\Modules\Reporting\Contracts;

use Illuminate\Support\Collection;

interface ReportInterface
{
    public function headers(): array;

    public function run(array $filters): Collection;
}
