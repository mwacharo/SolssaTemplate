<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(string $message = "Insufficient stock available.", int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
