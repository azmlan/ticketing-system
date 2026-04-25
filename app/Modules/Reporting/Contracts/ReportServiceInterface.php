<?php

namespace App\Modules\Reporting\Contracts;

use Illuminate\Support\Collection;

interface ReportServiceInterface
{
    public function run(string $type, array $filters): Collection;
}
