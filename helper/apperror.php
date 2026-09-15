<?php

declare(strict_types=1);

namespace App\Helper;

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
 * Helper response error.
 *
 * Bisa dipakai 4 cara:
 *   (new AppError())->process('Pesan', 400);                    // tanpa request & response
 *   (new AppError())->process('Pesan', 400, $request);          // hanya request (response dibuatkan)
 *   (new AppError())->process('Pesan', 400, null, $response);   // hanya response (render JSON ke response tsb)
 *   (new AppError())->process('Pesan', 400, $request, $response); // request + response (coba render Twig, fallback JSON)
 */
class AppError
{
    public function process(
        string $message,
        int $code = 500,
        ?ServerRequestInterface $request = null,
        ?ResponseInterface $response = null
    ): ResponseInterface {
        // FIX bug lama: `$response = null` (assignment) -> harus `$response === null`.
        // Jika response tidak diberikan, buatkan baru agar bisa dipakai standalone.
        if ($response === null) {
            $response = new SlimResponse();
        }

        // Jika request tersedia, coba render halaman HTML error via Twig.
        // Twig::fromRequest() throw RuntimeException kalau TwigMiddleware
        // belum terpasang, jadi harus try/catch dan fallback ke JSON.
        if ($request !== null) {
            try {
                $view = Twig::fromRequest($request);
                return $view->render($response->withStatus($code), 'error.html.twig', [
                    'message' => $message,
                    'status' => $code,
                ]);
            } catch (\Throwable) {
                // Fall through ke JSON di bawah.
            }
        }

        $data = [
            'message' => $message,
            'status' => $code,
        ];

        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));

        return $response
            ->withStatus($code)
            ->withHeader('Content-Type', 'application/json');
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
