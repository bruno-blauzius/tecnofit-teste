<?php

declare(strict_types=1);

namespace HyperfTest\Cases\UseCase\Schedule;

use App\Model\Account;
use App\Model\User;
use App\Model\AccountWithdraw;
use App\Model\AccountTransactionHistory;
use App\UseCase\Schedule\ScheduleUseCase;
use App\UseCase\Schedule\ProcessScheduledWithdrawUseCase;
use PHPUnit\Framework\TestCase;
use Hyperf\DbConnection\Db;
use DateTimeImmutable;

class ScheduleUseCaseTest extends TestCase
{
    private ScheduleUseCase $useCase;
    private ProcessScheduledWithdrawUseCase $processWithdrawUseCase;

    protected function setUp(): void
    {
        parent::setUp();

        // Limpar tabelas na ordem correta (por causa das foreign keys)
        if (\Hyperf\Database\Schema\Schema::hasTable('account_withdraw')) {
            Db::table('account_withdraw')->delete();
        }
        if (\Hyperf\Database\Schema\Schema::hasTable('account_transaction_history')) {
            Db::table('account_transaction_history')->delete();
        }
        if (\Hyperf\Database\Schema\Schema::hasTable('account')) {
            Db::table('account')->delete();
        }
        if (\Hyperf\Database\Schema\Schema::hasTable('users')) {
            Db::table('users')->delete();
        }

        $this->processWithdrawUseCase = new ProcessScheduledWithdrawUseCase(
            new Account(),
            new AccountTransactionHistory()
        );

        $this->useCase = new ScheduleUseCase(
            new AccountWithdraw(),
            new Account(),
            $this->processWithdrawUseCase
        );
    }

    public function testExecuteReturnsEmptyWhenNoScheduledWithdraws()
    {
        $result = $this->useCase->execute();

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(0, $result['errors']);
        $this->assertEmpty($result['results']);
    }

    public function testExecuteProcessesScheduledWithdrawsSuccessfully()
    {
        // Criar usuário
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);

        // Criar conta
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Test Account',
            'balance' => 1000.00,
        ]);

        // Criar saque agendado para processar agora
        $now = new DateTimeImmutable('now');
        $pastTime = $now->modify('-1 hour');

        $withdraw = AccountWithdraw::create([
            'account_id' => $account->id,
            'amount' => 100.00,
            'method' => 'pix',
            'scheduled' => true,
            'done' => false,
            'error' => false,
            'scheduled_for' => $pastTime->format('Y-m-d H:i:s'),
        ]);

        // Executar dentro de uma coroutine
        $result = null;
        \Hyperf\Coroutine\run(function () use (&$result) {
            $result = $this->useCase->execute();
        });

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['total']);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(0, $result['errors']);
        $this->assertCount(1, $result['results']);

        // Verificar resultado individual
        $withdrawResult = $result['results'][0];
        $this->assertEquals('success', $withdrawResult['status']);
        $this->assertEquals($withdraw->id, $withdrawResult['withdraw_id']);

        // Verificar que o saque foi marcado como processado
        $withdraw->refresh();
        $this->assertTrue((bool) $withdraw->done);
        $this->assertFalse((bool) $withdraw->error);
    }

    public function testExecuteProcessesMultipleScheduledWithdraws()
    {
        // Criar usuário
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);

        // Criar contas
        $account1 = Account::create([
            'user_id' => $user->id,
            'name' => 'Account 1',
            'balance' => 500.00,
        ]);

        $account2 = Account::create([
            'user_id' => $user->id,
            'name' => 'Account 2',
            'balance' => 800.00,
        ]);

        // Criar saques agendados
        $now = new DateTimeImmutable('now');
        $pastTime = $now->modify('-1 hour');

        AccountWithdraw::create([
            'account_id' => $account1->id,
            'amount' => 50.00,
            'method' => 'pix',
            'scheduled' => true,
            'done' => false,
            'error' => false,
            'scheduled_for' => $pastTime->format('Y-m-d H:i:s'),
        ]);

        AccountWithdraw::create([
            'account_id' => $account2->id,
            'amount' => 100.00,
            'method' => 'pix',
            'scheduled' => true,
            'done' => false,
            'error' => false,
            'scheduled_for' => $pastTime->format('Y-m-d H:i:s'),
        ]);

        // Executar dentro de uma coroutine
        $result = null;
        \Hyperf\Coroutine\run(function () use (&$result) {
            $result = $this->useCase->execute();
        });

        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, $result['processed']);
        $this->assertEquals(0, $result['errors']);
        $this->assertCount(2, $result['results']);
    }

    public function testExecuteHandlesInsufficientBalance()
    {
        // Criar usuário
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);

        // Criar conta com saldo insuficiente
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Test Account',
            'balance' => 50.00,
        ]);

        // Criar saque agendado maior que o saldo
        $now = new DateTimeImmutable('now');
        $pastTime = $now->modify('-1 hour');

        $withdraw = AccountWithdraw::create([
            'account_id' => $account->id,
            'amount' => 100.00,
            'method' => 'pix',
            'scheduled' => true,
            'done' => false,
            'error' => false,
            'scheduled_for' => $pastTime->format('Y-m-d H:i:s'),
        ]);

        // Executar dentro de uma coroutine
        $result = null;
        \Hyperf\Coroutine\run(function () use (&$result) {
            $result = $this->useCase->execute();
        });

        $this->assertEquals(1, $result['total']);
        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(1, $result['errors']);

        // Verificar que o erro foi registrado
        $withdrawResult = $result['results'][0];
        $this->assertEquals('error', $withdrawResult['status']);
        $this->assertStringContainsString('Saldo insuficiente', $withdrawResult['message']);

        // Verificar que o saque foi marcado com erro
        $withdraw->refresh();
        $this->assertFalse((bool) $withdraw->done);
        $this->assertTrue((bool) $withdraw->error);
        $this->assertNotNull($withdraw->error_reason);
    }

    public function testExecuteIgnoresFutureScheduledWithdraws()
    {
        // Criar usuário
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);

        // Criar conta
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Test Account',
            'balance' => 1000.00,
        ]);

        // Criar saque agendado para o futuro
        $now = new DateTimeImmutable('now');
        $futureTime = $now->modify('+1 day');

        AccountWithdraw::create([
            'account_id' => $account->id,
            'amount' => 100.00,
            'method' => 'pix',
            'scheduled' => true,
            'done' => false,
            'error' => false,
            'scheduled_for' => $futureTime->format('Y-m-d H:i:s'),
        ]);

        $result = $this->useCase->execute();

        // Não deve processar saques futuros
        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(0, $result['errors']);
    }

    public function testExecuteIgnoresAlreadyProcessedWithdraws()
    {
        // Criar usuário
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);

        // Criar conta
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Test Account',
            'balance' => 1000.00,
        ]);

        // Criar saque já processado
        $now = new DateTimeImmutable('now');
        $pastTime = $now->modify('-1 hour');

        AccountWithdraw::create([
            'account_id' => $account->id,
            'amount' => 100.00,
            'method' => 'pix',
            'scheduled' => true,
            'done' => true, // Já processado
            'error' => false,
            'scheduled_for' => $pastTime->format('Y-m-d H:i:s'),
        ]);

        $result = $this->useCase->execute();

        // Não deve processar saques já feitos
        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(0, $result['errors']);
    }

    public function testExecuteIgnoresWithdrawsWithErrors()
    {
        // Criar usuário
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);

        // Criar conta
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Test Account',
            'balance' => 1000.00,
        ]);

        // Criar saque com erro anterior
        $now = new DateTimeImmutable('now');
        $pastTime = $now->modify('-1 hour');

        AccountWithdraw::create([
            'account_id' => $account->id,
            'amount' => 100.00,
            'method' => 'pix',
            'scheduled' => true,
            'done' => false,
            'error' => true, // Teve erro antes
            'error_reason' => 'Erro anterior',
            'scheduled_for' => $pastTime->format('Y-m-d H:i:s'),
        ]);

        $result = $this->useCase->execute();

        // Não deve processar saques com erro
        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(0, $result['errors']);
    }
}
