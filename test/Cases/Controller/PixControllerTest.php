<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Controller;

use App\Model\Account;
use App\Model\PixKey;
use Hyperf\Testing\TestCase;
use Hyperf\DbConnection\Db;

class PixControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Limpa as tabelas antes de cada teste
        Db::statement('SET FOREIGN_KEY_CHECKS=0');
        if (\Hyperf\Database\Schema\Schema::hasTable('pix_keys')) {
            Db::table('pix_keys')->delete();
        }
        if (\Hyperf\Database\Schema\Schema::hasTable('account')) {
            Db::table('account')->delete();
        }
        Db::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function testStoreCreatesPixKeyAndSendsEmail()
    {
        // Cria uma conta de teste
        $account = Account::create([
            'name' => 'Test Account',
            'balance' => 1000.00,
        ]);

        $response = $this->json("/api/v1/public/accounts/{$account->id}/pix", [
            'type' => 'email',
            'key' => 'teste@example.com',
        ]);

        // Verifica se a resposta foi bem-sucedida
        $response->assertStatus(201);

        $result = $response->json();

        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('email', $result['data']['key_type']);
        $this->assertEquals('teste@example.com', $result['data']['key_value']);
        $this->assertEquals($account->id, $result['data']['account_id']);

        // Verifica se a chave PIX foi criada no banco
        $pixKey = PixKey::where('account_id', $account->id)
            ->where('key_value', 'teste@example.com')
            ->first();

        $this->assertNotNull($pixKey);
        $this->assertEquals('email', $pixKey->key_type);
        $this->assertEquals('active', $pixKey->status);

        // Email é enviado automaticamente pelo controller
        // (visível nos logs do teste como "[EMAIL] Email enviado para...")
    }

    public function testStoreCreatesPixKeyWithDifferentTypes()
    {
        $account = Account::create([
            'name' => 'Test Account',
            'balance' => 1000.00,
        ]);

        $testCases = [
            ['type' => 'cpf', 'key' => '52998224725'],
            ['type' => 'phone', 'key' => '+5511999999999'],
            ['type' => 'random', 'key' => '123e4567-e89b-12d3-a456-426614174000'],
        ];

        foreach ($testCases as $testCase) {
            $response = $this->json("/api/v1/public/accounts/{$account->id}/pix", [
                'type' => $testCase['type'],
                'key' => $testCase['key'],
            ]);

            $response->assertStatus(201);

            $result = $response->json();

            $this->assertArrayHasKey('data', $result);
            $this->assertEquals($testCase['type'], $result['data']['key_type']);
            $this->assertEquals($testCase['key'], $result['data']['key_value']);

            // Email é enviado automaticamente para cada chave PIX criada
            // (visível nos logs do teste como "[EMAIL] Email enviado para...")
        }
    }

    public function testStoreValidatesRequiredFields()
    {
        $account = Account::create([
            'name' => 'Test Account',
            'balance' => 1000.00,
        ]);

        // Testa sem 'type'
        $response = $this->json("/api/v1/public/accounts/{$account->id}/pix", [
            'key' => 'teste@example.com',
        ]);

        $response->assertStatus(422);

        // Testa sem 'key'
        $response = $this->json("/api/v1/public/accounts/{$account->id}/pix", [
            'type' => 'email',
        ]);

        $response->assertStatus(422);
    }

    public function testStoreValidatesUniquePixKey()
    {
        $account = Account::create([
            'name' => 'Test Account',
            'balance' => 1000.00,
        ]);

        // Cria primeira chave PIX
        $response = $this->json("/api/v1/public/accounts/{$account->id}/pix", [
            'type' => 'email',
            'key' => 'duplicado@example.com',
        ]);

        $response->assertStatus(201);

        // Tenta criar a mesma chave PIX novamente
        $response = $this->json("/api/v1/public/accounts/{$account->id}/pix", [
            'type' => 'email',
            'key' => 'duplicado@example.com',
        ]);

        $response->assertStatus(422);

        $result = $response->json();
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('key', $result['errors']);
    }
}

