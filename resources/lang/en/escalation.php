<?php

return [
    'condition_report' => [
        'title'              => 'Condition Report',
        'report_type'        => 'Report Type',
        'location'           => 'Location',
        'select_location'    => '-- Select Location --',
        'report_date'        => 'Report Date',
        'current_condition'  => 'Current Condition',
        'condition_analysis' => 'Condition Analysis',
        'required_action'    => 'Required Action',
        'attachments'        => 'Attachments (up to 5 files)',
        'submit'             => 'Submit Report',
    ],

    'validation' => [
        'report_type_required'        => 'Report type is required.',
        'current_condition_required'  => 'Current condition is required.',
        'condition_analysis_required' => 'Condition analysis is required.',
        'required_action_required'    => 'Required action is required.',
        'attachments_max'             => 'No more than 5 attachments may be uploaded.',
    ],
];
