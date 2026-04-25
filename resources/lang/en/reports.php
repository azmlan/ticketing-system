<?php

return [
    'title' => 'Reports',

    'types' => [
        'ticket_volume'       => 'Ticket Volume',
        'tickets_by_status'   => 'Tickets by Status',
        'tickets_by_category' => 'Tickets by Category',
        'tickets_by_priority' => 'Tickets by Priority',
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
        'period'    => 'Date',
        'count'     => 'Count',
        'status'    => 'Status',
        'category'  => 'Category',
        'priority'  => 'Priority',
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
];
