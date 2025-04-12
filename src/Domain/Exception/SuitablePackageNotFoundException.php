<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class SuitablePackageNotFoundException extends PackingException
{
    public function __construct(string $message = 'No suitable box found for the given products', int $code = 0, \Throwable|null $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
