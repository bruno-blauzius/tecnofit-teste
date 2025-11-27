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

namespace HyperfTest\Cases\Request;

use App\Request\AccountPostWithDrawRequest;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @internal
 * @coversNothing
 */
class AccountPostWithDrawRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = $this->createRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsMethodValidation()
    {
        $request = $this->createRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('method', $rules);
        $this->assertStringContainsString('required', $rules['method']);
        $this->assertStringContainsString('in:PIX', $rules['method']);
    }

    public function testRulesContainsPixValidation()
    {
        $request = $this->createRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('pix', $rules);
        $this->assertArrayHasKey('pix.type', $rules);
        $this->assertArrayHasKey('pix.key', $rules);

        $this->assertStringContainsString('required', $rules['pix']);
        $this->assertStringContainsString('array', $rules['pix']);
        $this->assertStringContainsString('in:email,cpf,cnpj,phone,random', $rules['pix.type']);
    }

    public function testRulesContainsAmountValidation()
    {
        $request = $this->createRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('amount', $rules);
        $this->assertStringContainsString('required', $rules['amount']);
        $this->assertStringContainsString('numeric', $rules['amount']);
        $this->assertStringContainsString('min:0.01', $rules['amount']);
    }

    public function testRulesContainsScheduleValidation()
    {
        $request = $this->createRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('schedule', $rules);
        $this->assertIsArray($rules['schedule']);
        $this->assertContains('nullable', $rules['schedule']);
    }

    public function testMessagesContainsAllRequiredMessages()
    {
        $request = $this->createRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('method.required', $messages);
        $this->assertArrayHasKey('pix.required', $messages);
        $this->assertArrayHasKey('pix.type.required', $messages);
        $this->assertArrayHasKey('pix.key.required', $messages);
        $this->assertArrayHasKey('amount.required', $messages);
        $this->assertArrayHasKey('schedule.date_format', $messages);
    }

    public function testAttributesContainsAllFields()
    {
        $request = $this->createRequest();
        $attributes = $request->attributes();

        $this->assertArrayHasKey('method', $attributes);
        $this->assertArrayHasKey('pix', $attributes);
        $this->assertArrayHasKey('pix.type', $attributes);
        $this->assertArrayHasKey('pix.key', $attributes);
        $this->assertArrayHasKey('amount', $attributes);
        $this->assertArrayHasKey('schedule', $attributes);
    }

    public function testMessagesAreStrings()
    {
        $request = $this->createRequest();
        $messages = $request->messages();

        foreach ($messages as $message) {
            $this->assertIsString($message);
            $this->assertNotEmpty($message);
        }
    }

    public function testAttributesAreStrings()
    {
        $request = $this->createRequest();
        $attributes = $request->attributes();

        foreach ($attributes as $attribute) {
            $this->assertIsString($attribute);
            $this->assertNotEmpty($attribute);
        }
    }

    private function createRequest(): AccountPostWithDrawRequest
    {
        $container = Mockery::mock(ContainerInterface::class);
        return new AccountPostWithDrawRequest($container);
    }
}
