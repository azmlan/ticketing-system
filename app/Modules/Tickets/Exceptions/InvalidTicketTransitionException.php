<?php

namespace App\Modules\Tickets\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidTicketTransitionException extends HttpException
{
    public function __construct(string $from, string $to, string $reason = '')
    {
        $message = "Invalid ticket transition from '{$from}' to '{$to}'";
        if ($reason !== '') {
            $message .= ": {$reason}";
        }

        parent::__construct(422, $message);
    }
}
