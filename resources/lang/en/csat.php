<?php

return [
    'prompt' => [
        'title' => 'Rate Your Experience',
        'intro' => 'How was your experience with :tech for ticket :number?',
        'subject_label' => 'Subject',
        'rating_label' => 'Rating',
        'rating_hint' => 'Select 1–5 stars',
        'comment_label' => 'Comment (optional)',
        'dismiss' => 'Remind me later',
        'submit' => 'Submit Rating',
    ],
    'section' => [
        'title' => 'Customer Satisfaction',
        'pending' => 'You can rate your experience with :tech below.',
        'expired' => 'The rating window for this ticket has closed.',
        'submitted_by_me' => 'Your rating',
        'submitted_tech' => 'Rating from requester',
        'submitted_mgr' => 'Customer rating',
        'no_rating' => 'No rating submitted.',
        'rating_label' => 'Rating',
        'comment_label' => 'Comment',
        'submitted_at' => 'Submitted',
    ],
    'validation' => [
        'rating_required' => 'Please select a rating.',
        'rating_range' => 'Rating must be between 1 and 5.',
        'already_rated' => 'This ticket has already been rated.',
    ],
];
