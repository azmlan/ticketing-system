<?php

namespace App\Modules\SLA\View\Components;

use Illuminate\View\Component;

class SlaStatusBadge extends Component
{
    public function __construct(
        public string $status,
        public string $type,
    ) {}

    public function colorClasses(): string
    {
        return match ($this->status) {
            'warning' => 'bg-warning/10 text-warning',
            'breached' => 'bg-danger/10 text-danger',
            default => 'bg-success/10 text-success',
        };
    }

    public function render()
    {
        return view('components.sla.status-badge');
    }
}
