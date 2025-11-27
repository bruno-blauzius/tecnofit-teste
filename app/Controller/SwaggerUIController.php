<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;

class SwaggerUIController
{
    public function json(ResponseInterface $response)
    {
        $jsonFile = BASE_PATH . '/storage/swagger/swagger.json';

        if (!file_exists($jsonFile)) {
            return $response->withStatus(404)->json([
                'error' => 'Swagger documentation not found. Please run: php generate-swagger.php'
            ]);
        }

        $json = file_get_contents($jsonFile);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withBody(new \Hyperf\HttpMessage\Stream\SwooleStream($json));
    }

    public function ui(ResponseInterface $response)
    {
        $htmlFile = BASE_PATH . '/public/swagger.html';

        if (!file_exists($htmlFile)) {
            return $response->withStatus(404)->json([
                'error' => 'Swagger UI not found.'
            ]);
        }

        $html = file_get_contents($htmlFile);

        return $response
            ->withHeader('Content-Type', 'text/html')
            ->withBody(new \Hyperf\HttpMessage\Stream\SwooleStream($html));
    }
}
