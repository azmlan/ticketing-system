<?php

return [

    'resolve_modal' => [
        'title'               => 'Resolve Ticket',
        'subtitle'            => 'Please fill in the resolution details before proceeding.',
        'summary'             => 'Resolution Summary',
        'root_cause'          => 'Root Cause',
        'steps_taken'         => 'Steps Taken',
        'parts_resources'     => 'Parts / Resources Used',
        'time_spent_minutes'  => 'Time Spent (minutes)',
        'resolution_type'     => 'Resolution Type',
        'select_type'         => 'Select resolution type',
        'cancel'              => 'Cancel',
        'submit'              => 'Confirm Resolution',
        'link_existing'       => 'Link to existing resolution',
        'write_new'           => 'Write new resolution',
        'link_notes'          => 'Link Notes',
        'search_resolutions'  => 'Search past resolutions...',
        'no_suggestions'      => 'No matching resolutions for the current category.',
        'selected_resolution' => 'Selected Resolution',
        'no_results'          => 'No matching resolutions found.',
    ],

    'resolution_type' => [
        'known_fix'            => 'Known Fix',
        'workaround'           => 'Workaround',
        'escalated_externally' => 'Escalated Externally',
        'other'                => 'Other',
    ],

    'auto_suggest' => [
        'title'          => 'Suggested Resolutions',
        'usage_count'    => 'Uses',
        'resolved_on'    => 'Resolved on',
        'show_more'      => 'Show more',
        'show_less'      => 'Show less',
        'empty'          => 'No past resolutions for this category.',
        'context_fields' => 'Custom fields from original ticket',
    ],

];
