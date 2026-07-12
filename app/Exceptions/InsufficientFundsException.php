<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientFundsException extends RuntimeException
{
    public function __construct(string $message = 'Insufficient wallet balance for this transaction.')
    {
        parent::__construct($message);
    }
}
