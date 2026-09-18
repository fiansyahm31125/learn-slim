<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

/**
 * Base exception untuk error HTTP yang aman ditampilkan ke klien.
 *
 * Aturan:
 * - $message adalah pesan aman (tidak mengandung DSN, path, SQL, stack trace).
 * - $statusCode adalah status HTTP yang sesuai.
 * - $details hanya berisi data aman (mis. validation errors, bukan raw exception).
 */
class HttpException extends RuntimeException
{
    /** @param array<string, mixed> $details */
    public function __construct(
        string $message = 'Terjadi kesalahan pada server',
        int $statusCode = 500,
        private array $details = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return (int) $this->getCode();
    }

    /** @return array<string, mixed> */
    public function getDetails(): array
    {
        return $this->details;
    }
}
