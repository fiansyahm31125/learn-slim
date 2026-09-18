<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Dilempar saat validasi input gagal (422).
 * Membawa daftar $errors berbentuk ['field' => ['pesan1', 'pesan2']].
 * Errors dianggap aman karena berasal dari ProductValidator (pesan statis),
 * bukan dari exception database / stack trace.
 */
class ValidationException extends HttpException
{
    /** @param array<string, string[]> $errors */
    public function __construct(
        private array $errors,
        string $message = 'Validasi gagal'
    ) {
        parent::__construct($message, 422, ['errors' => $errors]);
    }

    /** @return array<string, string[]> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
