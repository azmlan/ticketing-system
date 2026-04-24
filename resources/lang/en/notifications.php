<?php

return [
    'view_ticket' => 'View ticket :display_number',

    'ticket_created' => [
        'subject'  => 'Your request has been received — :display_number',
        'greeting' => 'Hello :name,',
        'body'     => 'Your request has been received successfully. Reference: :display_number — ":subject".',
    ],

    'ticket_assigned' => [
        'subject'  => 'A new ticket has been assigned to you — :display_number',
        'greeting' => 'Hello :name,',
        'body'     => 'Ticket :display_number — ":subject" has been assigned to you for processing.',
    ],

    'ticket_resolved' => [
        'subject'  => 'Your request has been resolved — :display_number',
        'greeting' => 'Hello :name,',
        'body'     => 'We are pleased to inform you that ticket :display_number — ":subject" has been resolved.',
    ],

    'ticket_closed' => [
        'subject'  => 'Your request has been closed — :display_number',
        'greeting' => 'Hello :name,',
        'body'     => 'Ticket :display_number — ":subject" has been closed.',
    ],

    'action_required' => [
        'subject'  => 'Action required — :display_number',
        'greeting' => 'Hello :name,',
        'body'     => 'Please review ticket :display_number — ":subject" and take the required action.',
    ],

    'form_rejected' => [
        'subject'  => 'Document rejected — :display_number',
        'greeting' => 'Hello :name,',
        'body'     => 'The submitted document for ticket :display_number — ":subject" has been rejected. Please resubmit.',
    ],

    'escalation_submitted' => [
        'subject'  => 'New escalation request — :display_number',
        'greeting' => 'Hello :name,',
        'body'     => 'A new escalation request has been submitted for ticket :display_number — ":subject". Please review.',
    ],

    'escalation_updated' => [
        'subject'  => 'Escalation status updated — :display_number',
        'greeting' => 'Hello :name,',
        'body'     => 'The escalation status for ticket :display_number — ":subject" has been updated.',
    ],

    'transfer_request' => [
        'subject'  => 'Ticket transfer request — :display_number',
        'greeting' => 'Hello :name,',
        'body'     => 'A transfer request for ticket :display_number — ":subject" has been sent to you. Please accept or decline.',
    ],
];
