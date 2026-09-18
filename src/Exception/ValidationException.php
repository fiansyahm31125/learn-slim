<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

/**
 * Dilempar saat validasi input gagal.
 * Membawa daftar $errors berbentuk ['field' => ['pesan1', 'pesan2']].
 */
class ValidationException extends RuntimeException
{
    /** @param array<string, string[]> $errors */
    public function __construct(
        private array $errors,
        string $message = 'Validasi gagal'
    ) {
        parent::__construct($message, 422);
    }

    /** @return array<string, string[]> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
