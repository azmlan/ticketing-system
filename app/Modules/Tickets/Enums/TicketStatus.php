<?php

namespace App\Modules\Tickets\Enums;

enum TicketStatus: string
{
    case AwaitingAssignment     = 'awaiting_assignment';
    case InProgress             = 'in_progress';
    case OnHold                 = 'on_hold';
    case AwaitingApproval       = 'awaiting_approval';
    case ActionRequired         = 'action_required';
    case AwaitingFinalApproval  = 'awaiting_final_approval';
    case Resolved               = 'resolved';
    case Closed                 = 'closed';
    case Cancelled              = 'cancelled';
}
