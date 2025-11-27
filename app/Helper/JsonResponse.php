<?php

declare(strict_types=1);

namespace App\Helper;

use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;

class JsonResponse
{
    public static function success(
        ResponseInterface $response,
        mixed $data,
        int $statusCode = 200,
        ?string $message = null
    ): ResponseInterface {
        $payload = ['success' => true];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        $payload['data'] = $data;

        return $response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(new SwooleStream(json_encode($payload, JSON_UNESCAPED_UNICODE)));
    }

    public static function error(
        ResponseInterface $response,
        string $message,
        int $statusCode = 400,
        ?array $errors = null
    ): ResponseInterface {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return $response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(new SwooleStream(json_encode($payload, JSON_UNESCAPED_UNICODE)));
    }
}
