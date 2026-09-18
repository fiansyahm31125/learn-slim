<?php

declare(strict_types=1);

namespace App\Exception;

/** 403 — terautentikasi tapi tidak diizinkan. */
class ForbiddenException extends HttpException
{
    public function __construct(string $message = 'Forbidden', ?\Throwable $previous = null)
    {
        parent::__construct($message, 403, [], $previous);
    }
}
