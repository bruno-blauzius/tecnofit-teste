<?php

declare(strict_types=1);

namespace HyperfTest\Cases\UseCase\Account;

use App\Model\Account;
use App\Model\AccountWithdraw;
use App\Model\AccountWithdrawPix;
use App\Model\AccountTransactionHistory;
use App\Model\PixKey;
use App\UseCase\Account\WithdrawRequest;
use App\UseCase\Account\WithdrawUseCase;
use App\UseCase\Account\Exception\AccountNotFoundException;
use App\UseCase\Account\Exception\InsufficientBalanceException;
use App\UseCase\Account\Exception\InvalidScheduleException;
use App\UseCase\Account\Exception\PixKeyNotFoundException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Hyperf\DbConnection\Db;

class WithdrawUseCaseTest extends TestCase
{
    private WithdrawUseCase $useCase;

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

        $container = \Hyperf\Context\ApplicationContext::getContainer();
        $this->useCase = new WithdrawUseCase(
            new Account(),
            new AccountWithdraw(),
            new AccountWithdrawPix(),
            new AccountTransactionHistory(),
            new PixKey(),
            $container
        );
    }

    public function testExecuteProcessesImmediateWithdraw()
    {
        $account = Account::create(['name' => 'Test Account', 'balance' => 1000]);

        // Criar chave PIX ativa para a conta
        PixKey::create([
            'account_id' => $account->id,
            'key_type' => 'email',
            'key_value' => 'test@example.com',
            'status' => 'active',
        ]);

        $request = new WithdrawRequest(
            accountId: $account->id,
            amount: 150.75,
            method: 'PIX',
            pixType: 'email',
            pixKey: 'test@example.com',
            scheduleAt: null
        );

        $result = $this->useCase->execute($request);

        $this->assertFalse($result['scheduled']);
        $this->assertSame(150.75, $result['amount']);
        $this->assertEqualsWithDelta(849.25, $result['balance'], 0.01);
        $this->assertArrayHasKey('withdraw_id', $result);

        $account->refresh();
        $this->assertSame('849.25', (string) $account->balance);
    }

    public function testExecuteProcessesScheduledWithdraw()
    {
        $account = Account::create(['name' => 'Test Account', 'balance' => 1000]);

        // Criar chave PIX ativa para a conta
        PixKey::create([
            'account_id' => $account->id,
            'key_type' => 'email',
            'key_value' => 'test@example.com',
            'status' => 'active',
        ]);

        $scheduleAt = new DateTimeImmutable('2025-12-01 10:00:00');

        $request = new WithdrawRequest(
            accountId: $account->id,
            amount: 200,
            method: 'PIX',
            pixType: 'email',
            pixKey: 'test@example.com',
            scheduleAt: $scheduleAt
        );

        $result = $this->useCase->execute($request);

        $this->assertTrue($result['scheduled']);
        $this->assertSame('2025-12-01 10:00:00', $result['schedule_at']);

        $account->refresh();
        $this->assertSame('1000.00', (string) $account->balance);
    }

    public function testExecuteThrowsExceptionForNonExistentAccount()
    {
        $this->expectException(AccountNotFoundException::class);
        $this->expectExceptionMessage('Conta não encontrada.');

        $request = new WithdrawRequest(
            accountId: 'invalid-id',
            amount: 100,
            method: 'PIX',
            pixType: 'email',
            pixKey: 'test@example.com',
            scheduleAt: null
        );

        $this->useCase->execute($request);
    }

    public function testExecuteThrowsExceptionForInsufficientBalance()
    {
        $account = Account::create(['name' => 'Test Account', 'balance' => 50]);

        // Criar chave PIX ativa para a conta
        PixKey::create([
            'account_id' => $account->id,
            'key_type' => 'email',
            'key_value' => 'test@example.com',
            'status' => 'active',
        ]);

        $this->expectException(InsufficientBalanceException::class);
        $this->expectExceptionMessage('Saldo insuficiente para o saque.');

        $request = new WithdrawRequest(
            accountId: $account->id,
            amount: 100,
            method: 'PIX',
            pixType: 'email',
            pixKey: 'test@example.com',
            scheduleAt: null
        );

        $this->useCase->execute($request);
    }

    public function testExecuteThrowsExceptionForZeroAmount()
    {
        $account = Account::create(['name' => 'Test Account', 'balance' => 100]);

        // Criar chave PIX ativa para a conta
        PixKey::create([
            'account_id' => $account->id,
            'key_type' => 'email',
            'key_value' => 'test@example.com',
            'status' => 'active',
        ]);

        $this->expectException(InsufficientBalanceException::class);
        $this->expectExceptionMessage('O valor do saque deve ser maior que zero.');

        $request = new WithdrawRequest(
            accountId: $account->id,
            amount: 0,
            method: 'PIX',
            pixType: 'email',
            pixKey: 'test@example.com',
            scheduleAt: null
        );

        $this->useCase->execute($request);
    }

    public function testExecuteThrowsExceptionForNegativeAmount()
    {
        $account = Account::create(['name' => 'Test Account', 'balance' => 100]);

        // Criar chave PIX ativa para a conta
        PixKey::create([
            'account_id' => $account->id,
            'key_type' => 'email',
            'key_value' => 'test@example.com',
            'status' => 'active',
        ]);

        $this->expectException(InsufficientBalanceException::class);

        $request = new WithdrawRequest(
            accountId: $account->id,
            amount: -100,
            method: 'PIX',
            pixType: 'email',
            pixKey: 'test@example.com',
            scheduleAt: null
        );

        $this->useCase->execute($request);
    }

    public function testExecuteThrowsExceptionForPastSchedule()
    {
        $account = Account::create(['name' => 'Test Account', 'balance' => 1000]);

        // Criar chave PIX ativa para a conta
        PixKey::create([
            'account_id' => $account->id,
            'key_type' => 'email',
            'key_value' => 'test@example.com',
            'status' => 'active',
        ]);

        $pastDate = new DateTimeImmutable('2020-01-01 10:00:00');

        $this->expectException(InvalidScheduleException::class);
        $this->expectExceptionMessage('Não é permitido agendar saque para o passado.');

        $request = new WithdrawRequest(
            accountId: $account->id,
            amount: 100,
            method: 'PIX',
            pixType: 'email',
            pixKey: 'test@example.com',
            scheduleAt: $pastDate
        );

        $this->useCase->execute($request);
    }

    public function testExecuteCreatesWithdrawAndPixRecords()
    {
        $account = Account::create(['name' => 'Test Account', 'balance' => 1000]);

        // Criar chave PIX ativa para a conta
        PixKey::create([
            'account_id' => $account->id,
            'key_type' => 'email',
            'key_value' => 'test@example.com',
            'status' => 'active',
        ]);

        $request = new WithdrawRequest(
            accountId: $account->id,
            amount: 100,
            method: 'PIX',
            pixType: 'email',
            pixKey: 'test@example.com',
            scheduleAt: null
        );

        $result = $this->useCase->execute($request);

        $withdraw = AccountWithdraw::find($result['withdraw_id']);
        $this->assertNotNull($withdraw);
        $this->assertSame($account->id, $withdraw->account_id);
        $this->assertSame('100.00', (string) $withdraw->amount);
        $this->assertTrue($withdraw->done);

        $pix = AccountWithdrawPix::where('account_withdraw_id', $withdraw->id)->first();
        $this->assertNotNull($pix);
        $this->assertSame('email', $pix->type);
        $this->assertSame('test@example.com', $pix->key_value);
    }

    public function testExecuteThrowsExceptionWhenNoActivePixKey()
    {
        $account = Account::create(['name' => 'Test Account', 'balance' => 1000]);

        $this->expectException(PixKeyNotFoundException::class);
        $this->expectExceptionMessage('Nenhuma chave PIX ativa encontrada para esta conta');

        $request = new WithdrawRequest(
            accountId: $account->id,
            amount: 100,
            method: 'PIX',
            pixType: 'email',
            pixKey: 'test@example.com',
            scheduleAt: null
        );

        $this->useCase->execute($request);
    }

    public function testExecuteRollsBackOnError()
    {
        $account = Account::create(['name' => 'Test Account', 'balance' => 1000]);

        // Criar chave PIX ativa para a conta
        PixKey::create([
            'account_id' => $account->id,
            'key_type' => 'email',
            'key_value' => 'test@example.com',
            'status' => 'active',
        ]);

        $initialBalance = $account->balance;

        try {
            $request = new WithdrawRequest(
                accountId: $account->id,
                amount: 100,
                method: 'PIX',
                pixType: 'invalid-type',
                pixKey: 'test@example.com',
                scheduleAt: null
            );

            $this->useCase->execute($request);
        } catch (\Throwable $e) {
            $account->refresh();
            $this->assertSame((string) $initialBalance, (string) $account->balance);
        }
    }
}
