<?php

declare(strict_types=1);

namespace HyperfTest\Cases\UseCase\Account;

use App\Model\Account;
use App\UseCase\Account\UpdateBalanceRequest;
use App\UseCase\Account\UpdateBalanceUseCase;
use App\Exception\BusinessException;
use PHPUnit\Framework\TestCase;
use Hyperf\DbConnection\Db;

class UpdateBalanceUseCaseTest extends TestCase
{
    private UpdateBalanceUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        if (\Hyperf\Database\Schema\Schema::hasTable('account')) {
            Db::table('account')->delete();
        }
        $this->useCase = new UpdateBalanceUseCase(new Account(), new \App\Model\AccountTransactionHistory());
    }

    public function testExecuteUpdatesBalanceSuccessfully()
    {
        // Create account
        $account = Account::create([
            'name' => 'Test Account',
            'balance' => 1000.00,
        ]);

        $request = new UpdateBalanceRequest(
            accountId: $account->id,
            balance: 500.50
        );

        $result = $this->useCase->execute($request);

        $this->assertIsArray($result);
        $this->assertSame($account->id, $result['id']);
        $this->assertEquals(1500.50, (float)$result['balance']);

        $this->assertDatabaseHas('account', [
            'id' => $account->id,
            'balance' => 1500.50,
        ]);
    }

    public function testExecuteUpdatesBalanceToZero()
    {
        $account = Account::create([
            'name' => 'Test Account',
            'balance' => 1000.00,
        ]);

        $request = new UpdateBalanceRequest(
            accountId: $account->id,
            balance: -1000.00
        );

        $result = $this->useCase->execute($request);

        $this->assertEquals(0.0, (float)$result['balance']);
    }

    public function testExecuteThrowsExceptionWhenAccountNotFound()
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Conta não encontrada.');

        $request = new UpdateBalanceRequest(
            accountId: 'non-existent-id',
            balance: 100.00
        );

        $this->useCase->execute($request);
    }

    public function testExecuteThrowsExceptionWhenBalanceIsNegative()
    {
        $account = Account::create([
            'name' => 'Test Account',
            'balance' => 1000.00,
        ]);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('O saldo não pode ser negativo.');

        $request = new UpdateBalanceRequest(
            accountId: $account->id,
            balance: -2000.00
        );

        $this->useCase->execute($request);
    }

    private function assertDatabaseHas(string $table, array $data): void
    {
        $query = Db::table($table);
        foreach ($data as $key => $value) {
            $query->where($key, $value);
        }
        $this->assertTrue($query->exists());
    }
}
