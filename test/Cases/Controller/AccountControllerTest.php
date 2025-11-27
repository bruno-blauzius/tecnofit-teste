<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Controller;

use App\Model\Account;
use App\Model\PixKey;
use Hyperf\Testing\TestCase;
use Hyperf\DbConnection\Db;

class AccountControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Db::statement('SET FOREIGN_KEY_CHECKS=0');
        if (\Hyperf\Database\Schema\Schema::hasTable('pix_keys')) {
            Db::table('pix_keys')->delete();
        }
        if (\Hyperf\Database\Schema\Schema::hasTable('account_withdraw_pix')) {
            Db::table('account_withdraw_pix')->delete();
        }
        if (\Hyperf\Database\Schema\Schema::hasTable('account_withdraw')) {
            Db::table('account_withdraw')->delete();
        }
        if (\Hyperf\Database\Schema\Schema::hasTable('account')) {
            Db::table('account')->delete();
        }
        Db::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function testStoreCreatesAccountSuccessfully()
    {
        $response = $this->json('/api/v1/public/accounts', [
            'name' => 'Test Account',
            'balance' => 1000.50,
        ]);

        $response->assertStatus(201);
        $result = $response->json();

        $this->assertArrayHasKey('data', $result);
        $this->assertEqualsWithDelta(1000.50, $result['data']['balance'], 0.01);
        $this->assertArrayHasKey('id', $result['data']);
    }

    public function testStoreValidatesRequiredFields()
    {
        $response = $this->json('/api/v1/public/accounts', []);

        $response->assertStatus(422);
        $result = $response->json();

        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('name', $result['errors']);
        $this->assertArrayHasKey('balance', $result['errors']);
    }

    public function testStoreValidatesBalanceIsNumeric()
    {
        $response = $this->json('/api/v1/public/accounts', [
            'name' => 'Test Account',
            'balance' => 'invalid',
        ]);

        $response->assertStatus(422);
        $result = $response->json();

        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('balance', $result['errors']);
    }

    public function testStoreValidatesBalanceMinimum()
    {
        $response = $this->json('/api/v1/public/accounts', [
            'name' => 'Test Account',
            'balance' => -10,
        ]);

        $response->assertStatus(422);
        $result = $response->json();

        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('balance', $result['errors']);
    }

    public function testIndexReturnsAllAccounts()
    {
        Account::create(['name' => 'Account 1', 'balance' => 100]);
        Account::create(['name' => 'Account 2', 'balance' => 200]);

        $response = $this->get('/api/v1/public/accounts');

        $response->assertStatus(200);
        $result = $response->json();

        $this->assertArrayHasKey('data', $result);
        $this->assertCount(2, $result['data']);
    }

    public function testIndexReturnsEmptyArrayWhenNoAccounts()
    {
        $response = $this->get('/api/v1/public/accounts');

        $response->assertStatus(200);
        $result = $response->json();

        $this->assertArrayHasKey('data', $result);
        $this->assertCount(0, $result['data']);
    }

    public function testWithdrawProcessesImmediateWithdraw()
    {
        $account = Account::create(['name' => 'Test Account', 'balance' => 1000]);

        PixKey::create([
            'account_id' => $account->id,
            'key_type' => 'email',
            'key_value' => 'test@example.com',
            'status' => 'active',
        ]);

        $response = $this->json("/api/v1/public/accounts/{$account->id}/balance/withdraw", [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com',
            ],
            'amount' => 150.75,
            'schedule' => null,
        ]);

        $response->assertStatus(201);
        $result = $response->json();

        $this->assertArrayHasKey('data', $result);
        $this->assertFalse($result['data']['scheduled']);
        $this->assertSame(150.75, $result['data']['amount']);

        $account->refresh();
        $this->assertSame('849.25', (string) $account->balance);
    }

    public function testWithdrawProcessesScheduledWithdraw()
    {
        $account = Account::create(['name' => 'Test Account', 'balance' => 1000]);

        PixKey::create([
            'account_id' => $account->id,
            'key_type' => 'email',
            'key_value' => 'test@example.com',
            'status' => 'active',
        ]);

        $response = $this->json("/api/v1/public/accounts/{$account->id}/balance/withdraw", [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com',
            ],
            'amount' => 150.75,
            'schedule' => '2025-12-01 10:00',
        ]);

        $response->assertStatus(201);
        $result = $response->json();

        $this->assertArrayHasKey('data', $result);
        $this->assertTrue($result['data']['scheduled']);
        $this->assertSame('2025-12-01 10:00:00', $result['data']['schedule_at']);

        $account->refresh();
        $this->assertSame('1000.00', (string) $account->balance);
    }

    public function testWithdrawReturns404ForNonExistentAccount()
    {
        $response = $this->json('/api/v1/public/accounts/invalid-uuid/balance/withdraw', [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com',
            ],
            'amount' => 100,
            'schedule' => null,
        ]);

        $response->assertStatus(404);
    }

    public function testWithdrawReturns422ForInsufficientBalance()
    {
        $account = Account::create(['name' => 'Test Account', 'balance' => 50]);

        PixKey::create([
            'account_id' => $account->id,
            'key_type' => 'email',
            'key_value' => 'test@example.com',
            'status' => 'active',
        ]);

        $response = $this->json("/api/v1/public/accounts/{$account->id}/balance/withdraw", [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com',
            ],
            'amount' => 100,
            'schedule' => null,
        ]);

        $response->assertStatus(422);
        $result = $response->json();

        $this->assertArrayHasKey('message', $result);
    }

    public function testWithdrawReturns422ForPastSchedule()
    {
        $account = Account::create(['name' => 'Test Account', 'balance' => 1000]);

        PixKey::create([
            'account_id' => $account->id,
            'key_type' => 'email',
            'key_value' => 'test@example.com',
            'status' => 'active',
        ]);

        $response = $this->json("/api/v1/public/accounts/{$account->id}/balance/withdraw", [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'test@example.com',
            ],
            'amount' => 100,
            'schedule' => '2020-01-01 10:00',
        ]);

        $response->assertStatus(422);
    }

    public function testWithdrawValidatesRequiredFields()
    {
        $account = Account::create(['name' => 'Test Account', 'balance' => 1000]);

        $response = $this->json("/api/v1/public/accounts/{$account->id}/balance/withdraw", []);

        $response->assertStatus(422);
        $result = $response->json();

        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('method', $result['errors']);
        $this->assertArrayHasKey('amount', $result['errors']);
    }

    public function testWithdrawReturns422WhenPixKeyDoesNotExist()
    {
        $account = Account::create(['name' => 'Test Account', 'balance' => 1000]);

        // Não criar nenhuma chave PIX para a conta

        $response = $this->json("/api/v1/public/accounts/{$account->id}/balance/withdraw", [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'nonexistent@example.com',
            ],
            'amount' => 100,
            'schedule' => null,
        ]);

        $response->assertStatus(422);
        $result = $response->json();

        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsString('chave PIX', $result['message']);
    }
}
