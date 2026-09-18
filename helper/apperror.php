<?php

declare(strict_types=1);

namespace App\Helper;

use App\Exception\HttpException;
use App\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response as SlimResponse;
use Slim\Views\Twig;

// Guard agar file aman di-require berkali-kali
// (mis. via composer "files" autoload + require manual).
if (class_exists(AppError::class, false)) {
    return;
}

/**
 * Renderer error terpusat.
 *
 * Prinsip:
 * - Response klien SELALU terstruktur: JSON {success:false,message,status[,errors][,debug]}
 *   atau HTML via error.html.twig (hanya message+status yang aman).
 * - TIDAK PERNAH membocorkan: stack trace, path file, DSN, SQL, token, detail PDO/Doctrine
 *   kecuali APP_DEBUG=true (itu pun hanya `type` + pesan aman, tanpa trace).
 * - Detail internal hanya masuk ke server log via error_log().
 *
 * Bisa dipakai 4 cara (backward-compat):
 *   (new AppError())->process('Pesan', 400);
 *   (new AppError())->process('Pesan', 400, $request);
 *   (new AppError())->process('Pesan', 400, null, $response);
 *   (new AppError())->process('Pesan', 400, $request, $response);
 */
class AppError
{
    public static function isDebug(): bool
    {
        $raw = $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: 'false';

        return in_array(strtolower(trim((string) $raw)), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Render JSON error terstruktur.
     *
     * @param array<string, string[]> $errors
     */
    public static function json(
        string $message,
        int $code = 500,
        array $errors = [],
        ?ResponseInterface $response = null
    ): ResponseInterface {
        $response ??= new SlimResponse();
        $payload = self::envelope($message, $code, $errors);
        $response->getBody()->write(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response
            ->withStatus($code)
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Mapping Throwable -> response aman + logging internal.
     * Dipakai oleh Slim default error handler (config/errorHandler.php).
     */
    public static function fromThrowable(
        ServerRequestInterface $request,
        \Throwable $e,
        ?ResponseInterface $response = null
    ): ResponseInterface {
        [$status, $safeMessage, $errors] = self::map($e);
        self::log($request, $e, $status);

        // Content negotiation: browser (Accept: text/html) dapat halaman Twig,
        // API client (Accept: application/json) dapat JSON. Default: JSON.
        if (self::wantsHtml($request)) {
            try {
                $view = Twig::fromRequest($request);
                $response ??= new SlimResponse();
                // Hanya kirim message+status yang aman ke template.
                return $view->render($response->withStatus($status), 'error.html.twig', [
                    'message' => $safeMessage,
                    'status' => $status,
                ]);
            } catch (\Throwable) {
                // Fall through ke JSON di bawah.
            }
        }

        return self::json($safeMessage, $status, $errors, $response);
    }

    /**
     * @return array{0:int, 1:string, 2:array<string, string[]>}
     */
    private static function map(\Throwable $e): array
    {
        // 1. Validasi -> 422 + errors apa adanya (aman: dari ProductValidator).
        if ($e instanceof ValidationException) {
            return [422, 'Validasi gagal', $e->getErrors()];
        }

        // 2. HttpException domain (401/403/404/...) -> pesan aman langsung.
        if ($e instanceof HttpException) {
            $details = $e->getDetails();
            $errors = (isset($details['errors']) && is_array($details['errors']))
                ? $details['errors']
                : [];
            return [$e->getStatusCode(), $e->getMessage(), $errors];
        }

        // 3. Slim routing errors (pakai class-string agar tidak fatal bila Slim berubah).
        if ($e instanceof \Slim\Exception\HttpNotFoundException) {
            return [404, 'Rute tidak ditemukan', []];
        }
        if ($e instanceof \Slim\Exception\HttpMethodNotAllowedException) {
            return [405, 'Metode tidak diizinkan', []];
        }
        if ($e instanceof \Slim\Exception\HttpException) {
            $code = $e->getCode();
            $status = ($code >= 400 && $code < 600) ? (int) $code : 500;
            // Jangan teruskan $e->getMessage() mentah (bisa berisi path); pakai generik.
            $safe = $status === 500 ? 'Terjadi kesalahan pada server' : $e->getTitle();
            return [$status, $safe !== '' ? $safe : 'Terjadi kesalahan pada server', []];
        }

        // 4. Database errors -> JANGAN bocorkan SQL/DSN. Log saja, klien dapat generik.
        if (
            $e instanceof \PDOException
            || $e instanceof \Doctrine\DBAL\Exception
            || $e instanceof \Doctrine\ORM\ORMException
            || str_contains($e::class, 'Doctrine')
        ) {
            return [500, 'Gagal mengakses database', []];
        }

        // 5. Twig render errors -> generik (detail path template hanya di log).
        if (str_contains($e::class, 'Twig')) {
            return [500, 'Gagal merender halaman', []];
        }

        // 6. Fallback: pesan generik. Pengecualian: bila APP_DEBUG, boleh tampilkan
        //    pesan exception TAPI tetap tanpa trace/file penuh (lihat envelope()).
        if (self::isDebug()) {
            $msg = $e->getMessage() !== '' ? $e->getMessage() : 'Terjadi kesalahan pada server';
            // Potong pesan yang dicurigai berisi path absolut / SQL.
            return [500, mb_substr($msg, 0, 300), []];
        }

        return [500, 'Terjadi kesalahan pada server', []];
    }

    private static function log(ServerRequestInterface $request, \Throwable $e, int $status): void
    {
        // Server-side only: boleh detail lengkap. Tidak pernah dikirim ke klien.
        $method = $request->getMethod();
        $uri = (string) $request->getUri();
        // Buang query `token=` dari log agar token tidak tersimpan di log file.
        $uri = (string) preg_replace('/([?&])token=[^&]*/i', '$1token=***', $uri);
        error_log(sprintf(
            '[%s] %s %s -> %s: %s in %s:%d',
            date('c'),
            $method,
            $uri,
            $e::class,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));
    }

    private static function wantsHtml(ServerRequestInterface $request): bool
    {
        return str_contains(strtolower($request->getHeaderLine('Accept')), 'text/html');
    }

    /**
     * @param array<string, string[]> $errors
     * @return array<string, mixed>
     */
    private static function envelope(string $message, int $code, array $errors = []): array
    {
        $payload = [
            'success' => false,
            'message' => $message,
            'status' => $code,
        ];
        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }

        return $payload;
    }

    // ---- Backward-compat API ----

    public function process(
        string $message,
        int $code = 500,
        ?ServerRequestInterface $request = null,
        ?ResponseInterface $response = null
    ): ResponseInterface {
        // Samakan format lama {message,status} dengan envelope baru {success,message,status}.
        // Jika request menghendaki HTML, coba Twig dulu (hanya message+status aman).
        if ($request !== null && self::wantsHtml($request)) {
            try {
                $view = Twig::fromRequest($request);
                $response ??= new SlimResponse();
                return $view->render($response->withStatus($code), 'error.html.twig', [
                    'message' => $message,
                    'status' => $code,
                ]);
            } catch (\Throwable) {
                // Fall through ke JSON.
            }
        }

        return self::json($message, $code, [], $response);
    }

    /**
     * Shortcut statis agar pemakaian lebih ringkas:
     *   return AppError::make('Produk tidak ditemukan', 404, $request, $response);
     */
    public static function make(
        string $message,
        int $code = 500,
        ?ServerRequestInterface $request = null,
        ?ResponseInterface $response = null
    ): ResponseInterface {
        return (new self())->process($message, $code, $request, $response);
    }
}

// Alias backward-compat untuk kode lama yang memakai `new \Error(...)`
// (nama `Error` bertabrakan dengan class bawaan PHP, jadi jangan dipakai lagi).
if (!class_exists('Error', false)) {
    class_alias(AppError::class, 'Error');
}
