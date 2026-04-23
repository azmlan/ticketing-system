<?php

return [
    'create' => [
        'title'       => 'Create New Ticket',
        'subject'     => 'Subject',
        'description' => 'Description',
        'category'    => 'Category',
        'subcategory' => 'Subcategory',
        'submit'      => 'Submit Ticket',
        'success'     => 'Ticket created successfully.',
    ],
    'validation' => [
        'subject_required'       => 'Subject is required.',
        'subject_max'            => 'Subject may not exceed 255 characters.',
        'description_required'   => 'Description is required.',
        'category_required'      => 'Please select a category.',
        'category_invalid'       => 'The selected category is invalid.',
        'subcategory_required'   => 'A subcategory is required for this category.',
        'subcategory_invalid'    => 'The selected subcategory is invalid.',
    ],
    'rate_limit_exceeded' => 'You have exceeded the ticket creation limit. Please try again later.',
    'select_category'     => 'Select a category...',
    'select_subcategory'  => 'Select a subcategory...',
];
