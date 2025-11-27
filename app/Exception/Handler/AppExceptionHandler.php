<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Exception\Handler;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use Hyperf\Validation\ValidationException as ValidationException;

class AppExceptionHandler extends ExceptionHandler
{
    public function __construct(protected StdoutLoggerInterface $logger)
    {
    }

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->logger->error(sprintf('%s[%s] in %s', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile()));
        $this->logger->error($throwable->getTraceAsString());

        // If it's a validation exception, return the prepared response (FormRequest builds it)
        if ($throwable instanceof ValidationException) {
            $resp = $throwable->getResponse();
            if ($resp instanceof ResponseInterface) {
                return $resp;
            }

            // Fallback: build a JSON 422 response with the validation errors
            $payload = json_encode([
                'message' => 'The given data was invalid.',
                'errors' => $throwable->errors(),
            ], JSON_UNESCAPED_UNICODE);

            return $response->withHeader('Content-Type', 'application/json')
                ->withStatus($throwable->status)
                ->withBody(new SwooleStream($payload));
        }

        return $response->withHeader('Server', 'Hyperf')->withStatus(500)->withBody(new SwooleStream('Internal Server Error.'));
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
