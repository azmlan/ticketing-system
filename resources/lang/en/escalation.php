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

    'maintenance_request' => [
        'title'     => 'Maintenance Request',
        'export_ar' => 'Export in Arabic',
        'export_en' => 'Export in English',
    ],

    'upload_signed' => [
        'title'               => 'Upload Signed Maintenance Request',
        'policy_notice'       => 'Please review the maintenance request document, sign it offline, then upload the signed copy in PDF or DOCX format.',
        'disclaimer_reminder' => 'By uploading the signed document you acknowledge the disclaimer contained within the maintenance request.',
        'download_title'      => 'Download Maintenance Request',
        'file_label'          => 'Signed Document (PDF or DOCX, max 10 MB)',
        'submit'              => 'Upload Signed Document',
    ],

    'final_review' => [
        'title'                    => 'Review Signed Maintenance Request',
        'signed_document'          => 'View Signed Document',
        'rejection_count'          => 'Rejection Count',
        'prior_notes'              => 'Prior Rejection Notes',
        'approve'                  => 'Approve',
        'approve_confirm'          => 'Are you sure you want to approve this maintenance request?',
        'reject_resubmit'          => 'Reject (Resubmit)',
        'reject_permanently'       => 'Reject Permanently',
        'review_notes'             => 'Review Notes',
        'review_notes_placeholder' => 'Enter reason for rejection...',
        'close_reason'             => 'Close Reason',
        'submit_rejection'         => 'Submit Rejection',
        'submit_permanent'         => 'Reject Permanently',
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
        'signed_file_required'        => 'The signed document is required.',
        'signed_file_invalid_type'    => 'Only PDF and DOCX files are accepted.',
        'signed_file_too_large'       => 'File exceeds the 10 MB maximum.',
        'close_reason_required'       => 'A close reason is required for permanent rejection.',
    ],
];
