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

    'review' => [
        'title'                    => 'Review Condition Report',
        'submitted_by'             => 'Submitted By',
        'submitted_at'             => 'Submitted At',
        'report_date'              => 'Report Date',
        'approve'                  => 'Approve',
        'approve_confirm'          => 'Are you sure you want to approve this condition report?',
        'reject'                   => 'Reject',
        'reject_with_notes'        => 'Reject with Notes',
        'review_notes'             => 'Review Notes',
        'review_notes_placeholder' => 'Enter reason for rejection...',
        'submit_rejection'         => 'Submit Rejection',
        'cancel'                   => 'Cancel',
    ],

    'validation' => [
        'report_type_required'        => 'Report type is required.',
        'current_condition_required'  => 'Current condition is required.',
        'condition_analysis_required' => 'Condition analysis is required.',
        'required_action_required'    => 'Required action is required.',
        'attachments_max'             => 'No more than 5 attachments may be uploaded.',
        'review_notes_required'       => 'Review notes are required when rejecting.',
        'review_notes_max'            => 'Review notes may not exceed 1000 characters.',
    ],
];
