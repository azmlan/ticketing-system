<?php

namespace App\Modules\Escalation\Jobs;

use App\Modules\Escalation\Services\MaintenanceRequestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateMaintenanceRequestDocxJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $ticketUlid,
        public readonly string $locale,
    ) {}

    public function handle(MaintenanceRequestService $service): void
    {
        $service->generate($this->ticketUlid, $this->locale);
    }
}
