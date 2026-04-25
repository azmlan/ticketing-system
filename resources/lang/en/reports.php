<?php

return [
    'title' => 'Reports',

    'types' => [
        'ticket_volume'       => 'Ticket Volume',
        'tickets_by_status'   => 'Tickets by Status',
        'tickets_by_category' => 'Tickets by Category',
        'tickets_by_priority' => 'Tickets by Priority',
        'avg_resolution_time' => 'Avg Resolution Time',
        'tech_performance'    => 'Tech Performance',
        'team_workload'       => 'Team Workload',
        'escalation_summary'  => 'Escalation Summary',
        'sla_compliance'      => 'SLA Compliance',
        'sla_breaches'        => 'SLA Breaches',
        'csat_overview'       => 'CSAT Overview',
        'csat_by_tech'        => 'CSAT by Tech',
    ],

    'filters' => [
        'report_type' => 'Report Type',
        'date_from'   => 'Date From',
        'date_to'     => 'Date To',
        'category'    => 'Category',
        'priority'    => 'Priority',
        'group'       => 'Group',
        'tech'        => 'Technician',
        'status'      => 'Status',
        'all'         => 'All',
        'run'         => 'Run Report',
    ],

    'columns' => [
        'period'             => 'Date',
        'count'              => 'Count',
        'status'             => 'Status',
        'category'           => 'Category',
        'priority'           => 'Priority',
        'avg_hours'          => 'Avg Hours to Resolve',
        'tech_name'          => 'Technician',
        'resolved_count'     => 'Resolved',
        'avg_csat'           => 'Avg CSAT',
        'sla_compliance_pct' => 'SLA Compliance',
        'open_count'         => 'Open Tickets',
        'triggered'          => 'Triggered',
        'approved'           => 'Approved',
        'rejected'           => 'Rejected',
        'total_count'        => 'Total',
        'within_sla_count'   => 'Within SLA',
        'compliance_pct'     => 'Compliance %',
        'target_hours'       => 'Target (hrs)',
        'actual_hours'       => 'Actual (hrs)',
        'submitted_count'    => 'Submitted',
        'response_rate'      => 'Response Rate',
        'avg_rating'         => 'Avg Rating',
        'rating_1'           => '1 Star',
        'rating_2'           => '2 Stars',
        'rating_3'           => '3 Stars',
        'rating_4'           => '4 Stars',
        'rating_5'           => '5 Stars',
        'rating_count'       => 'Ratings',
        'lowest_rating'      => 'Lowest Rating',
    ],

    'labels' => [
        'uncategorised' => 'Uncategorised',
        'none'          => 'None',
        'no_data'       => 'No data for the selected filters.',
        'select_dates'  => 'Select a date range to run the report.',
    ],

    'validation' => [
        'date_from_required' => 'The start date is required.',
        'date_to_required'   => 'The end date is required.',
        'date_order'         => 'The end date must be on or after the start date.',
    ],

    'export' => [
        'download_csv'  => 'Download CSV',
        'download_xlsx' => 'Download XLSX',
        'queue_csv'     => 'Queue CSV Export',
        'queue_xlsx'    => 'Queue XLSX Export',
        'queued_notice' => 'Your export is being generated. You will receive an email when it is ready.',

        'notification_subject' => 'Your export is ready',
        'notification_body'    => 'Your :format export has been generated and is ready to download.',
        'notification_action'  => 'Download Export',
        'notification_expires' => 'This download link will expire after 24 hours.',

        // Standard column headers
        'ticket_number' => 'Ticket #',
        'subject'       => 'Subject',
        'status'        => 'Status',
        'priority'      => 'Priority',
        'category'      => 'Category',
        'subcategory'   => 'Subcategory',
        'group'         => 'Group',
        'assigned_tech' => 'Assigned Tech',
        'requester'     => 'Requester',
        'created_at'    => 'Created At',
        'resolved_at'   => 'Resolved At',
        'closed_at'     => 'Closed At',

        // SLA column headers
        'sla_response_target_mins'     => 'Response Target (mins)',
        'sla_response_actual_mins'     => 'Response Actual (mins)',
        'sla_response_status'          => 'Response SLA Status',
        'sla_resolution_target_mins'   => 'Resolution Target (mins)',
        'sla_resolution_actual_mins'   => 'Resolution Actual (mins)',
        'sla_resolution_status'        => 'Resolution SLA Status',
        'sla_total_paused_mins'        => 'Total Paused Time (mins)',

        // SLA status values
        'sla_statuses' => [
            'on_track' => 'On Track',
            'warning'  => 'Warning',
            'breached' => 'Breached',
        ],

        // CSAT column headers
        'csat_rating'       => 'CSAT Rating',
        'csat_comment'      => 'CSAT Comment',
        'csat_submitted_at' => 'CSAT Submitted At',
        'csat_status'       => 'CSAT Status',

        // CSAT status values
        'csat_statuses' => [
            'pending'   => 'Pending',
            'submitted' => 'Submitted',
            'expired'   => 'Expired',
        ],
    ],
];
