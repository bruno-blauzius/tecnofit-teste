<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Helper;

use App\Helper\JsonResponse;
use Hyperf\HttpMessage\Server\Response;
use PHPUnit\Framework\TestCase;

class JsonResponseTest extends TestCase
{
    public function testSuccessReturnsCorrectStructure()
    {
        $response = new Response();
        $data = ['id' => 1, 'name' => 'Test'];

        $result = JsonResponse::success($response, $data);

        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame('application/json', $result->getHeaderLine('Content-Type'));

        $body = json_decode($result->getBody()->getContents(), true);
        $this->assertArrayHasKey('success', $body);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('data', $body);
        $this->assertSame($data, $body['data']);
    }

    public function testSuccessWithCustomStatusCode()
    {
        $response = new Response();
        $data = ['created' => true];

        $result = JsonResponse::success($response, $data, 201);

        $this->assertSame(201, $result->getStatusCode());
    }

    public function testSuccessWithCustomMessage()
    {
        $response = new Response();
        $data = [];
        $message = 'Operation completed successfully';

        $result = JsonResponse::success($response, $data, 200, $message);

        $body = json_decode($result->getBody()->getContents(), true);
        $this->assertArrayHasKey('message', $body);
        $this->assertSame($message, $body['message']);
    }

    public function testSuccessWithoutMessage()
    {
        $response = new Response();
        $data = [];

        $result = JsonResponse::success($response, $data);

        $body = json_decode($result->getBody()->getContents(), true);
        $this->assertArrayNotHasKey('message', $body);
    }

    public function testErrorReturnsCorrectStructure()
    {
        $response = new Response();
        $message = 'An error occurred';

        $result = JsonResponse::error($response, $message);

        $this->assertSame(400, $result->getStatusCode());
        $this->assertSame('application/json', $result->getHeaderLine('Content-Type'));

        $body = json_decode($result->getBody()->getContents(), true);
        $this->assertArrayHasKey('success', $body);
        $this->assertFalse($body['success']);
        $this->assertArrayHasKey('message', $body);
        $this->assertSame($message, $body['message']);
    }

    public function testErrorWithCustomStatusCode()
    {
        $response = new Response();
        $message = 'Not found';

        $result = JsonResponse::error($response, $message, 404);

        $this->assertSame(404, $result->getStatusCode());
    }

    public function testErrorWithErrors()
    {
        $response = new Response();
        $message = 'Validation failed';
        $errors = ['name' => ['Name is required'], 'email' => ['Invalid email']];

        $result = JsonResponse::error($response, $message, 422, $errors);

        $body = json_decode($result->getBody()->getContents(), true);
        $this->assertArrayHasKey('errors', $body);
        $this->assertSame($errors, $body['errors']);
    }

    public function testErrorWithoutErrors()
    {
        $response = new Response();
        $message = 'Error';

        $result = JsonResponse::error($response, $message);

        $body = json_decode($result->getBody()->getContents(), true);
        $this->assertArrayNotHasKey('errors', $body);
    }

    public function testSuccessReturnsValidJson()
    {
        $response = new Response();
        $data = ['test' => 'value'];

        $result = JsonResponse::success($response, $data);

        $bodyContent = $result->getBody()->getContents();
        $this->assertNotFalse(json_decode($bodyContent));
        $this->assertSame(JSON_ERROR_NONE, json_last_error());
    }

    public function testErrorReturnsValidJson()
    {
        $response = new Response();
        $message = 'Error message';

        $result = JsonResponse::error($response, $message);

        $bodyContent = $result->getBody()->getContents();
        $this->assertNotFalse(json_decode($bodyContent));
        $this->assertSame(JSON_ERROR_NONE, json_last_error());
    }
}
