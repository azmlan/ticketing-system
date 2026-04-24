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
            'warning' => 'bg-yellow-100 text-yellow-800',
            'breached' => 'bg-red-100 text-red-800',
            default => 'bg-green-100 text-green-800',
        };
    }

    public function render()
    {
        return view('components.sla.status-badge');
    }
}
