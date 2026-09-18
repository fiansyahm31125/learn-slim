<?php

declare(strict_types=1);

use App\Helper\AppError;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Middleware\ErrorMiddleware;

require_once __DIR__ . '/../helper/apperror.php';

function attachErrorHandler(App $app, ErrorMiddleware $errorMiddleware): void
{
    $errorMiddleware->setDefaultErrorHandler(
        function (
            ServerRequestInterface $request,
            \Throwable $exception,
            bool $displayErrorDetails,
            bool $logErrors,
            bool $logErrorDetails
        ) use ($app): ResponseInterface {
            return AppError::fromThrowable($request, $exception);
        }
    );
}
