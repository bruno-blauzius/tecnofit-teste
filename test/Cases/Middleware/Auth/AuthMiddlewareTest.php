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

namespace HyperfTest\Cases\Middleware\Auth;

use App\Middleware\Auth\AuthMiddleware;
use Firebase\JWT\JWT;
use Hyperf\HttpMessage\Server\Request;
use Hyperf\HttpMessage\Server\Response;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 * @coversNothing
 */
class AuthMiddlewareTest extends TestCase
{
    private string $jwtSecret = 'test-secret-key';

    private $container;

    protected function setUp(): void
    {
        parent::setUp();
        putenv("JWT_SECRET={$this->jwtSecret}");

        $response = new Response();
        $this->container = Mockery::mock(ContainerInterface::class);
        $this->container->shouldReceive('get')
            ->with(Response::class)
            ->andReturn($response);
    }

    protected function tearDown(): void
    {
        putenv('JWT_SECRET');
        parent::tearDown();
    }

    public function testProcessAllowsValidToken()
    {
        $token = $this->generateValidToken();

        $request = $this->createMockRequest([
            'Authorization' => "Bearer {$token}",
        ]);

        $handler = $this->createMockHandler();
        $container = Mockery::mock(ContainerInterface::class);
        $middleware = new AuthMiddleware($container);

        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessReturns401WhenNoAuthorizationHeader()
    {
        $request = $this->createMockRequest([]);
        $handler = $this->createMockHandler();
        $middleware = new AuthMiddleware($this->container);

        $response = $middleware->process($request, $handler);

        $this->assertSame(401, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertArrayHasKey('message', $body);
    }

    public function testProcessReturns401WhenInvalidTokenFormat()
    {
        $request = $this->createMockRequest([
            'Authorization' => 'InvalidFormat',
        ]);

        $handler = $this->createMockHandler();
        $middleware = new AuthMiddleware($this->container);

        $response = $middleware->process($request, $handler);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testProcessReturns401WhenTokenIsExpired()
    {
        $expiredToken = $this->generateExpiredToken();

        $request = $this->createMockRequest([
            'Authorization' => "Bearer {$expiredToken}",
        ]);

        $handler = $this->createMockHandler();
        $middleware = new AuthMiddleware($this->container);

        $response = $middleware->process($request, $handler);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testProcessReturns401WhenTokenSignatureIsInvalid()
    {
        $invalidToken = $this->generateTokenWithWrongSecret();

        $request = $this->createMockRequest([
            'Authorization' => "Bearer {$invalidToken}",
        ]);

        $handler = $this->createMockHandler();
        $middleware = new AuthMiddleware($this->container);

        $response = $middleware->process($request, $handler);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testProcessAcceptsBearerTokenCaseInsensitive()
    {
        $token = $this->generateValidToken();

        $request = $this->createMockRequest([
            'Authorization' => "bearer {$token}",
        ]);

        $handler = $this->createMockHandler();
        $middleware = new AuthMiddleware($this->container);

        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    private function generateValidToken(): string
    {
        $payload = [
            'user_id' => 123,
            'exp' => time() + 3600,
            'iat' => time(),
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    private function generateExpiredToken(): string
    {
        $payload = [
            'user_id' => 123,
            'exp' => time() - 3600,
            'iat' => time() - 7200,
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    private function generateTokenWithWrongSecret(): string
    {
        $payload = [
            'user_id' => 123,
            'exp' => time() + 3600,
            'iat' => time(),
        ];

        return JWT::encode($payload, 'wrong-secret', 'HS256');
    }

    private function createMockRequest(array $headers): ServerRequestInterface
    {
        $request = new Request('GET', '/test');

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }

    private function createMockHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };
    }
}
