<?php

declare(strict_types=1);

namespace App\Support;

/**
 * File logger ringan tanpa dependensi tambahan.
 *
 * - Output: var/log/app-YYYY-MM-DD.log (rotasi harian), satu baris per event.
 * - Format: [ISO8601] LEVEL: message context={json}
 * - Tidak pernah melempar exception (gagal tulis = diam) agar logging
 *   tidak merusak response aplikasi.
 * - Sanitizes data sensitif: token, password, authorization, cookie, dsn.
 */
class AppLogger
{
    private const LEVELS = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];

    private static ?self $instance = null;

    private string $logDir;
    private string $minLevel;

    /** @var list<string> pola key sensitif (case-insensitive, substring match) */
    private const SENSITIVE_KEYS = [
        'password', 'passwd', 'pwd', 'token', 'secret', 'authorization',
        'cookie', 'set-cookie', 'api_key', 'apikey', 'access_token', 'dsn',
    ];

    public function __construct(string $logDir, string $minLevel = 'debug')
    {
        $this->logDir = rtrim($logDir, '/\\');
        $level = strtolower(trim($minLevel));
        $this->minLevel = isset(self::LEVELS[$level]) ? $level : 'debug';
    }

    public static function init(string $logDir, string $minLevel = 'debug'): self
    {
        self::$instance = new self($logDir, $minLevel);

        return self::$instance;
    }

    public static function get(): self
    {
        // Fallback aman bila init() belum dipanggil (mis. unit test / CLI):
        // tulis ke var/log relatif project root.
        if (self::$instance === null) {
            self::$instance = new self(dirname(__DIR__, 2) . '/var/log', 'debug');
        }

        return self::$instance;
    }

    /** Untuk test: reset singleton antar skenario. */
    public static function reset(): void
    {
        self::$instance = null;
    }

    public function debug(string $message, array $context = []): void
    {
        $this->write('debug', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->write('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('error', $message, $context);
    }

    /**
     * Catat error/exception dengan detail server-side (boleh lengkap).
     * Detail ini TIDAK dikirim ke klien — hanya ke file log.
     */
    public function logException(
        string $method,
        string $uri,
        \Throwable $e,
        int $status
    ): void {
        $this->write('error', sprintf('%s %s -> %d %s', $method, $this->redactUri($uri), $status, $e::class), [
            'status' => $status,
            'exception' => $e::class,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }

    /**
     * Catat aktivitas request (dipakai RequestLoggingMiddleware).
     * Level otomatis: >=500 error, >=400 warning, selebihnya info.
     */
    public function logRequest(
        string $method,
        string $uri,
        int $status,
        float $durationMs,
        array $extra = []
    ): void {
        $level = $status >= 500 ? 'error' : ($status >= 400 ? 'warning' : 'info');
        $this->write($level, sprintf(
            '%s %s -> %d (%.1f ms)',
            $method,
            $this->redactUri($uri),
            $status,
            $durationMs
        ), array_merge(['status' => $status, 'duration_ms' => round($durationMs, 1)], $extra));
    }

    private function write(string $level, string $message, array $context = []): void
    {
        try {
            if (self::LEVELS[$level] < self::LEVELS[$this->minLevel]) {
                return;
            }
            if (!is_dir($this->logDir) && !@mkdir($this->logDir, 0775, true) && !is_dir($this->logDir)) {
                return;
            }
            $file = $this->logDir . '/app-' . date('Y-m-d') . '.log';
            $line = sprintf(
                '[%s] %s: %s%s%s',
                date('c'),
                strtoupper($level),
                $message,
                empty($context) ? '' : ' context=',
                empty($context) ? '' : json_encode(
                    $this->redact($context),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                )
            );
            @file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Throwable) {
            // Logging tidak boleh memecahkan aplikasi.
        }
    }

    private function redactUri(string $uri): string
    {
        // Samarkan nilai query sensitif: ?token=abc&password=x -> token=***.
        return (string) preg_replace_callback(
            '/([?&](?:password|passwd|pwd|token|secret|api_?key|access_token)=)([^&]*)/i',
            static fn(array $m): string => $m[1] . '***',
            $uri
        );
    }

    /**
     * Samarkan value untuk key sensitif secara rekursif.
     *
     * @param mixed $data
     * @return mixed
     */
    private function redact(mixed $data): mixed
    {
        if (is_array($data)) {
            $out = [];
            foreach ($data as $k => $v) {
                $out[$k] = $this->isSensitiveKey((string) $k) ? '***' : $this->redact($v);
            }

            return $out;
        }

        return $data;
    }

    private function isSensitiveKey(string $key): bool
    {
        $k = strtolower($key);
        foreach (self::SENSITIVE_KEYS as $needle) {
            if (str_contains($k, $needle)) {
                return true;
            }
        }

        return false;
    }
}
