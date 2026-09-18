<?php

declare(strict_types=1);

namespace App\Exception;

/** 401 — kredensial hilang / tidak valid. Jangan bocorkan token yang diharapkan. */
class UnauthorizedException extends HttpException
{
    public function __construct(string $message = 'Unauthorized', ?\Throwable $previous = null)
    {
        parent::__construct($message, 401, [], $previous);
    }
}
