<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Request;

use App\Request\AccountStoreRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class AccountStoreRequestTest extends TestCase
{
    private function createRequest(): AccountStoreRequest
    {
        $container = \Mockery::mock(ContainerInterface::class);
        return new AccountStoreRequest($container);
    }

    public function testAuthorizeReturnsTrue()
    {
        $request = $this->createRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsBalanceValidation()
    {
        $request = $this->createRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('balance', $rules);
        $this->assertStringContainsString('required', $rules['balance']);
        $this->assertStringContainsString('numeric', $rules['balance']);
        $this->assertStringContainsString('min:0', $rules['balance']);
    }

    public function testMessagesContainsBalanceValidations()
    {
        $request = $this->createRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('balance.required', $messages);
        $this->assertArrayHasKey('balance.numeric', $messages);
        $this->assertArrayHasKey('balance.min', $messages);
    }

    public function testAttributesContainsBalance()
    {
        $request = $this->createRequest();
        $attributes = $request->attributes();

        $this->assertArrayHasKey('balance', $attributes);
        $this->assertIsString($attributes['balance']);
    }
}
