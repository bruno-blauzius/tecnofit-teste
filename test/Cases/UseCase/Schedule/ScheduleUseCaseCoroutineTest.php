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
use Swoole\Coroutine;

/**
 * Testes de processamento paralelo com coroutines
 * Testa o comportamento das coroutines no processamento
 *
 * IMPORTANTE: Estes testes devem rodar fora do ambiente co-phpunit
 * Execute com: php vendor/bin/phpunit test/Cases/UseCase/Schedule/ScheduleUseCaseCoroutineTest.php
 */
class ScheduleUseCaseCoroutineTest extends TestCase
{
    private ScheduleUseCase $useCase;
    private ProcessScheduledWithdrawUseCase $processWithdrawUseCase;

    protected function setUp(): void
    {
        parent::setUp();

        // Garantir que estamos fora de contexto de coroutine
        if (Coroutine::getCid() > 0) {
            $this->markTestSkipped('Este teste deve rodar fora do ambiente de coroutine (use phpunit sem co-phpunit)');
        }

        $this->cleanDatabase();
        $this->initializeUseCases();
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
        parent::tearDown();
    }

    private function cleanDatabase(): void
    {
        Db::statement('SET FOREIGN_KEY_CHECKS=0;');
        Db::table('account_withdraw')->truncate();
        Db::table('account_transaction_history')->truncate();
        Db::table('account')->truncate();
        Db::table('users')->truncate();
        Db::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function initializeUseCases(): void
    {
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

    public function testProcessesSingleWithdrawWithCoroutine()
    {
        $user = $this->createTestUser();
        $account = $this->createTestAccount($user->id, 1000.00);

        $pastTime = (new DateTimeImmutable('now'))->modify('-1 hour');

        $withdraw = AccountWithdraw::create([
            'account_id' => $account->id,
            'amount' => 100.00,
            'method' => 'pix',
            'scheduled' => true,
            'done' => false,
            'error' => false,
            'scheduled_for' => $pastTime->format('Y-m-d H:i:s'),
        ]);

        $result = null;
        \Hyperf\Coroutine\run(function () use (&$result) {
            $result = $this->useCase->execute();
        });

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['total']);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(0, $result['errors']);

        $withdraw->refresh();
        $this->assertTrue((bool) $withdraw->done);
    }

    public function testProcessesMultipleWithdrawsInParallel()
    {
        $user = $this->createTestUser();
        $account = $this->createTestAccount($user->id, 10000.00);

        $pastTime = (new DateTimeImmutable('now'))->modify('-1 hour');

        // Criar 5 saques agendados
        $withdrawIds = [];
        for ($i = 0; $i < 5; $i++) {
            $withdraw = AccountWithdraw::create([
                'account_id' => $account->id,
                'amount' => 100.00,
                'method' => 'pix',
                'scheduled' => true,
                'done' => false,
                'error' => false,
                'scheduled_for' => $pastTime->format('Y-m-d H:i:s'),
            ]);
            $withdrawIds[] = $withdraw->id;
        }

        $result = null;
        \Hyperf\Coroutine\run(function () use (&$result) {
            $result = $this->useCase->execute();
        });

        $this->assertEquals(5, $result['total']);
        $this->assertEquals(5, $result['processed']);
        $this->assertEquals(0, $result['errors']);

        // Verificar que todos foram processados
        foreach ($withdrawIds as $withdrawId) {
            $withdraw = AccountWithdraw::find($withdrawId);
            $this->assertTrue((bool) $withdraw->done);
            $this->assertFalse((bool) $withdraw->error);
        }

        // Verificar saldo final
        $account->refresh();
        $this->assertEquals(9500.00, $account->balance);
    }

    public function testProcessesUpTo10WithdrawsSimultaneously()
    {
        $user = $this->createTestUser();
        $account = $this->createTestAccount($user->id, 20000.00);

        $pastTime = (new DateTimeImmutable('now'))->modify('-1 hour');

        // Criar 15 saques (mais que o limite de 10 paralelos)
        for ($i = 0; $i < 15; $i++) {
            AccountWithdraw::create([
                'account_id' => $account->id,
                'amount' => 100.00,
                'method' => 'pix',
                'scheduled' => true,
                'done' => false,
                'error' => false,
                'scheduled_for' => $pastTime->format('Y-m-d H:i:s'),
            ]);
        }

        $result = null;
        \Hyperf\Coroutine\run(function () use (&$result) {
            $result = $this->useCase->execute();
        });

        $this->assertEquals(15, $result['total']);
        $this->assertEquals(15, $result['processed']);
    }

    public function testHandlesMixedSuccessAndFailureInParallel()
    {
        $user = $this->createTestUser();

        // Criar duas contas: uma com saldo, outra sem
        $accountWithBalance = $this->createTestAccount($user->id, 500.00);
        $accountWithoutBalance = Account::create([
            'name' => 'Account Without Balance',
            'balance' => 50.00,
        ]);

        $pastTime = (new DateTimeImmutable('now'))->modify('-1 hour');

        // 3 saques com saldo suficiente
        for ($i = 0; $i < 3; $i++) {
            AccountWithdraw::create([
                'account_id' => $accountWithBalance->id,
                'amount' => 100.00,
                'method' => 'pix',
                'scheduled' => true,
                'done' => false,
                'error' => false,
                'scheduled_for' => $pastTime->format('Y-m-d H:i:s'),
            ]);
        }

        // 2 saques sem saldo suficiente
        for ($i = 0; $i < 2; $i++) {
            AccountWithdraw::create([
                'account_id' => $accountWithoutBalance->id,
                'amount' => 100.00,
                'method' => 'pix',
                'scheduled' => true,
                'done' => false,
                'error' => false,
                'scheduled_for' => $pastTime->format('Y-m-d H:i:s'),
            ]);
        }

        $result = null;
        \Hyperf\Coroutine\run(function () use (&$result) {
            $result = $this->useCase->execute();
        });

        $this->assertEquals(5, $result['total']);
        $this->assertEquals(3, $result['processed']);
        $this->assertEquals(2, $result['errors']);
        $this->assertCount(3, $result['results']);
        $this->assertCount(2, $result['failed']);
    }

    public function testCoroutinePerformanceImprovementWithMultipleWithdraws()
    {
        $user = $this->createTestUser();
        $account = $this->createTestAccount($user->id, 100000.00);

        $pastTime = (new DateTimeImmutable('now'))->modify('-1 hour');

        // Criar 10 saques
        for ($i = 0; $i < 10; $i++) {
            AccountWithdraw::create([
                'account_id' => $account->id,
                'amount' => 100.00,
                'method' => 'pix',
                'scheduled' => true,
                'done' => false,
                'error' => false,
                'scheduled_for' => $pastTime->format('Y-m-d H:i:s'),
            ]);
        }

        $startTime = microtime(true);

        $result = null;
        \Hyperf\Coroutine\run(function () use (&$result) {
            $result = $this->useCase->execute();
        });

        $executionTime = microtime(true) - $startTime;

        // Verificar que processou todos
        $this->assertEquals(10, $result['total']);
        $this->assertEquals(10, $result['processed']);

        // Verificar que o tempo de execução é razoável
        // Com processamento paralelo, deve ser menor que processar sequencialmente
        $this->assertLessThan(5.0, $executionTime, 'Processamento deve ser rápido com coroutines');
    }

    private function createTestUser(): User
    {
        return User::create([
            'name' => 'Test User Coroutine',
            'email' => 'test_coroutine_' . uniqid() . '@example.com',
            'password' => hash('sha256', 'password'),
        ]);
    }

    private function createTestAccount(string $userId, float $balance = 1000.00): Account
    {
        $account = Account::create([
            'name' => 'Test Account Coroutine',
            'balance' => $balance,
        ]);

        $user = User::find($userId);
        $user->account_id = $account->id;
        $user->save();

        return $account;
    }
}
