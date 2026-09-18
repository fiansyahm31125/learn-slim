<?php

declare(strict_types=1);

namespace App\Exception;

/** 404 — resource tidak ditemukan. Pesan aman, tanpa detail internal. */
class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Data tidak ditemukan', ?\Throwable $previous = null)
    {
        parent::__construct($message, 404, [], $previous);
    }
}
