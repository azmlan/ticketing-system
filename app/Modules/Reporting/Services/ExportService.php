<?php

namespace App\Modules\Reporting\Services;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExportService
{
    public function exportHeaders(): array
    {
        return array_merge(
            $this->standardHeaders(),
            $this->slaHeaders(),
            $this->csatHeaders(),
            $this->customFieldHeaders()
        );
    }

    public function exportRows(array $filters): array
    {
        $locale = app()->getLocale();
        $customFields = $this->customFieldColumns();

        $query = $this->buildBaseQuery($locale);
        $this->applyFilters($query, $filters);

        return $query
            ->orderBy('tickets.created_at', 'desc')
            ->get()
            ->map(fn ($row) => $this->mapRow($row, $locale, $customFields))
            ->toArray();
    }

    private function standardHeaders(): array
    {
        return [
            __('reports.export.ticket_number'),
            __('reports.export.subject'),
            __('reports.export.status'),
            __('reports.export.priority'),
            __('reports.export.category'),
            __('reports.export.subcategory'),
            __('reports.export.group'),
            __('reports.export.assigned_tech'),
            __('reports.export.requester'),
            __('reports.export.created_at'),
            __('reports.export.resolved_at'),
            __('reports.export.closed_at'),
        ];
    }

    private function slaHeaders(): array
    {
        return [
            __('reports.export.sla_response_target_mins'),
            __('reports.export.sla_response_actual_mins'),
            __('reports.export.sla_response_status'),
            __('reports.export.sla_resolution_target_mins'),
            __('reports.export.sla_resolution_actual_mins'),
            __('reports.export.sla_resolution_status'),
            __('reports.export.sla_total_paused_mins'),
        ];
    }

    private function csatHeaders(): array
    {
        return [
            __('reports.export.csat_rating'),
            __('reports.export.csat_comment'),
            __('reports.export.csat_submitted_at'),
            __('reports.export.csat_status'),
        ];
    }

    private function customFieldHeaders(): array
    {
        $locale = app()->getLocale();

        return array_map(
            fn ($f) => $locale === 'ar' ? $f->label_ar : $f->label_en,
            $this->customFieldColumns()
        );
    }

    private function buildBaseQuery(string $locale): Builder
    {
        $nameCol = $locale === 'ar' ? 'name_ar' : 'name_en';

        return DB::table('tickets')
            ->select([
                'tickets.id as ticket_id',
                'tickets.display_number',
                'tickets.subject',
                'tickets.status',
                'tickets.priority',
                "categories.{$nameCol} as category_name",
                "subcategories.{$nameCol} as subcategory_name",
                "grp.{$nameCol} as group_name",
                'tech.full_name as tech_name',
                'requester.full_name as requester_name',
                'tickets.created_at',
                'tickets.resolved_at',
                'tickets.closed_at',
                'ticket_sla.response_target_minutes',
                'ticket_sla.response_elapsed_minutes',
                'ticket_sla.response_status',
                'ticket_sla.resolution_target_minutes',
                'ticket_sla.resolution_elapsed_minutes',
                'ticket_sla.resolution_status',
                DB::raw('(SELECT COALESCE(SUM(spl.duration_minutes), 0) FROM sla_pause_logs spl WHERE spl.ticket_sla_id = ticket_sla.id) as total_paused_minutes'),
                'csat_ratings.rating as csat_rating',
                'csat_ratings.comment as csat_comment',
                'csat_ratings.submitted_at as csat_submitted_at',
                'csat_ratings.status as csat_status',
            ])
            ->leftJoin('categories', 'categories.id', '=', 'tickets.category_id')
            ->leftJoin('subcategories', 'subcategories.id', '=', 'tickets.subcategory_id')
            ->leftJoin('groups as grp', 'grp.id', '=', 'tickets.group_id')
            ->leftJoin('users as tech', 'tech.id', '=', 'tickets.assigned_to')
            ->leftJoin('users as requester', 'requester.id', '=', 'tickets.requester_id')
            ->leftJoin('ticket_sla', 'ticket_sla.ticket_id', '=', 'tickets.id')
            ->leftJoin('csat_ratings', 'csat_ratings.ticket_id', '=', 'tickets.id')
            ->whereNull('tickets.deleted_at');
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['date_from']) && ! empty($filters['date_to'])) {
            $query->whereBetween('tickets.created_at', [
                Carbon::parse($filters['date_from'])->startOfDay(),
                Carbon::parse($filters['date_to'])->endOfDay(),
            ]);
        }

        if (! empty($filters['category_id'])) {
            $query->where('tickets.category_id', $filters['category_id']);
        }

        if (! empty($filters['priority'])) {
            $query->where('tickets.priority', $filters['priority']);
        }

        if (! empty($filters['group_id'])) {
            $query->where('tickets.group_id', $filters['group_id']);
        }

        if (! empty($filters['tech_id'])) {
            $query->where('tickets.assigned_to', $filters['tech_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('tickets.status', $filters['status']);
        }
    }

    private function mapRow(object $row, string $locale, array $customFields): array
    {
        $cells = [
            $row->display_number,
            $row->subject,
            $row->status ? __('tickets.status.'.$row->status) : '',
            $row->priority ? __('tickets.priority.'.$row->priority) : '',
            $row->category_name ?? '',
            $row->subcategory_name ?? '',
            $row->group_name ?? '',
            $row->tech_name ?? '',
            $row->requester_name ?? '',
            $row->created_at ?? '',
            $row->resolved_at ?? '',
            $row->closed_at ?? '',
            // SLA
            $row->response_target_minutes ?? '',
            $row->response_elapsed_minutes ?? '',
            $row->response_status ? __('reports.export.sla_statuses.'.$row->response_status) : '',
            $row->resolution_target_minutes ?? '',
            $row->resolution_elapsed_minutes ?? '',
            $row->resolution_status ? __('reports.export.sla_statuses.'.$row->resolution_status) : '',
            $row->total_paused_minutes ?? 0,
            // CSAT
            $row->csat_rating ?? '',
            $row->csat_comment ?? '',
            $row->csat_submitted_at ?? '',
            $row->csat_status ? __('reports.export.csat_statuses.'.$row->csat_status) : '',
        ];

        foreach ($customFields as $field) {
            $cells[] = $this->getCustomFieldValue($row->ticket_id, $field->field_id);
        }

        return $cells;
    }

    private function customFieldColumns(): array
    {
        if (! Schema::hasTable('custom_fields') || ! Schema::hasTable('ticket_custom_field_values')) {
            return [];
        }

        return DB::table('custom_fields')
            ->select(['custom_fields.id as field_id', 'custom_fields.name_ar as label_ar', 'custom_fields.name_en as label_en'])
            ->join('ticket_custom_field_values', 'ticket_custom_field_values.custom_field_id', '=', 'custom_fields.id')
            ->groupBy('custom_fields.id', 'custom_fields.name_ar', 'custom_fields.name_en')
            ->orderBy('custom_fields.display_order')
            ->get()
            ->unique('field_id')
            ->values()
            ->all();
    }

    private function getCustomFieldValue(string $ticketId, string $fieldId): string
    {
        $value = DB::table('ticket_custom_field_values')
            ->where('ticket_id', $ticketId)
            ->where('custom_field_id', $fieldId)
            ->value('value');

        return $value ?? '';
    }
}
