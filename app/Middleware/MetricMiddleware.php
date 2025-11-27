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

namespace App\Middleware;

use Hyperf\Metric\Contract\CounterInterface;
use Hyperf\Metric\Contract\HistogramInterface;
use Hyperf\Metric\Contract\MetricFactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class MetricMiddleware implements MiddlewareInterface
{
    private CounterInterface $httpRequestsTotal;

    private HistogramInterface $httpRequestDuration;

    public function __construct(ContainerInterface $container)
    {
        $factory = $container->get(MetricFactoryInterface::class);

        $this->httpRequestsTotal = $factory->makeCounter(
            'http_requests_total',
            ['method', 'path', 'status_code']
        );

        $this->httpRequestDuration = $factory->makeHistogram(
            'http_request_duration_seconds',
            ['method', 'path']
        );
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();
        $path = $this->normalizePath($request->getUri()->getPath());

        $startTime = microtime(true);

        try {
            $response = $handler->handle($request);
            $statusCode = (string) $response->getStatusCode();

            $this->httpRequestsTotal
                ->with($method, $path, $statusCode)
                ->add(1);

            return $response;
        } catch (Throwable $e) {
            $this->httpRequestsTotal
                ->with($method, $path, '500')
                ->add(1);

            throw $e;
        } finally {
            $duration = microtime(true) - $startTime;
            $this->httpRequestDuration->with($method, $path)->put($duration);
        }
    }

    private function normalizePath(string $path): string
    {
        // Normaliza paths com IDs para métricas consistentes
        // /api/v1/accounts/123 -> /api/v1/accounts/{id}
        $path = preg_replace('/\/\d+/', '/{id}', $path);

        return $path ?: '/';
    }
}
