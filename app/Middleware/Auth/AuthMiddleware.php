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

namespace App\Middleware\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Hyperf\HttpMessage\Server\Response as HttpResponse;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

 // ou equivalente da sua lib de resposta

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected ContainerInterface $container
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');

        $secret = getenv('JWT_SECRET');
        $alg = 'HS256';

        if (empty($authHeader) || ! preg_match('/^Bearer\s+/i', $authHeader)) {
            return $this->unauthorized('Não encontrado ou cabeçalho de autorização inválido.');
        }

        $token = trim(preg_replace('/^Bearer\s+/i', '', $authHeader));
        if ($token === '') {
            return $this->unauthorized('O token para autenticação é requirido.');
        }

        try {
            $decoded = JWT::decode($token, new Key($secret, $alg));
            $request = $request->withAttribute('user', $decoded);
        } catch (Throwable $e) {
            return $this->unauthorized($e->getMessage());
        }
        return $handler->handle($request);
    }

    protected function unauthorized(string $message): ResponseInterface
    {
        $response = $this->container->get(HttpResponse::class);
        return $response
            ->withStatus(401)
            ->withAddedHeader('Content-Type', 'application/json')
            ->withBody(new SwooleStream(
                json_encode(['message' => $message], JSON_UNESCAPED_UNICODE)
            ));
    }
}
