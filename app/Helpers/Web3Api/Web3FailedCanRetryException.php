<?php

namespace App\Helpers\Web3Api;

use Exception;
use Throwable;

class Web3FailedCanRetryException extends Exception
{
    public function __construct(string $message = "", int $code = 20000, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
