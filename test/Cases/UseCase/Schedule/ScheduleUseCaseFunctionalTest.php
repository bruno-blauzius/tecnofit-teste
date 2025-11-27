<?php

declare(strict_types=1);

namespace HyperfTest\Cases\UseCase\Schedule;

use App\Model\Account;
use App\Model\User;
use App\Model\AccountWithdraw;
use App\Model\AccountTransactionHistory;
use App\UseCase\Schedule\ScheduleUseCase;
use App\UseCase\Schedule\ProcessScheduledWithdrawUseCase;
use HyperfTest\HttpTestCase;
use Hyperf\DbConnection\Db;
use DateTimeImmutable;

/**
 * Testes funcionais sem coroutines
 * Testa a lógica de negócio e validações
 */
class ScheduleUseCaseFunctionalTest extends HttpTestCase
{
    private ScheduleUseCase $useCase;
    private ProcessScheduledWithdrawUseCase $processWithdrawUseCase;

    protected function setUp(): void
    {
        parent::setUp();
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

    public function testReturnsEmptyResultWhenNoScheduledWithdraws()
    {
        $result = $this->useCase->execute();

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(0, $result['errors']);
        $this->assertEmpty($result['results']);
    }

    public function testIgnoresFutureScheduledWithdraws()
    {
        $user = $this->createTestUser();
        $account = $this->createTestAccount($user->id);

        $futureTime = (new DateTimeImmutable('now'))->modify('+1 hour');

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

        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['processed']);
    }

    public function testIgnoresAlreadyProcessedWithdraws()
    {
        $user = $this->createTestUser();
        $account = $this->createTestAccount($user->id);

        $pastTime = (new DateTimeImmutable('now'))->modify('-1 hour');

        AccountWithdraw::create([
            'account_id' => $account->id,
            'amount' => 100.00,
            'method' => 'pix',
            'scheduled' => true,
            'done' => true,
            'error' => false,
            'scheduled_for' => $pastTime->format('Y-m-d H:i:s'),
        ]);

        $result = $this->useCase->execute();

        $this->assertEquals(0, $result['total']);
    }

    public function testIgnoresWithdrawsWithErrors()
    {
        $user = $this->createTestUser();
        $account = $this->createTestAccount($user->id);

        $pastTime = (new DateTimeImmutable('now'))->modify('-1 hour');

        AccountWithdraw::create([
            'account_id' => $account->id,
            'amount' => 100.00,
            'method' => 'pix',
            'scheduled' => true,
            'done' => false,
            'error' => true,
            'error_reason' => 'Previous error',
            'scheduled_for' => $pastTime->format('Y-m-d H:i:s'),
        ]);

        $result = $this->useCase->execute();

        $this->assertEquals(0, $result['total']);
    }

    public function testFindsEligibleScheduledWithdraws()
    {
        $user = $this->createTestUser();
        $account = $this->createTestAccount($user->id, 1000.00);

        $pastTime = (new DateTimeImmutable('now'))->modify('-1 hour');

        AccountWithdraw::create([
            'account_id' => $account->id,
            'amount' => 100.00,
            'method' => 'pix',
            'scheduled' => true,
            'done' => false,
            'error' => false,
            'scheduled_for' => $pastTime->format('Y-m-d H:i:s'),
        ]);

        $result = $this->useCase->execute();

        $this->assertEquals(1, $result['total']);
        $this->assertCount(1, $result['results']);
    }

    public function testHandlesInsufficientBalanceGracefully()
    {
        $user = $this->createTestUser();
        $account = $this->createTestAccount($user->id, 50.00);

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

        $result = $this->useCase->execute();

        $this->assertEquals(1, $result['total']);
        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(1, $result['errors']);

        // Verificar que há erros nos resultados
        $this->assertNotEmpty($result['results']);
        $failedResult = $result['results'][0];
        $this->assertEquals('error', $failedResult['status']);
        $this->assertStringContainsString('Saldo insuficiente', $failedResult['message']);

        // Verificar que o saque foi marcado com erro
        $withdraw->refresh();
        $this->assertFalse((bool) $withdraw->done);
        $this->assertTrue((bool) $withdraw->error);
        $this->assertNotNull($withdraw->error_reason);
    }

    public function testReturnsCorrectStructureInResults()
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

        $result = $this->useCase->execute();

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('processed', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('results', $result);

        $this->assertIsInt($result['total']);
        $this->assertIsInt($result['processed']);
        $this->assertIsInt($result['errors']);
        $this->assertIsArray($result['results']);
    }

    private function createTestUser(): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => 'test_' . uniqid() . '@example.com',
            'password' => hash('sha256', 'password'),
        ]);
    }

    private function createTestAccount(string $userId, float $balance = 1000.00): Account
    {
        $account = Account::create([
            'name' => 'Test Account',
            'balance' => $balance,
        ]);

        // Associar conta ao usuário
        $user = User::find($userId);
        $user->account_id = $account->id;
        $user->save();

        return $account;
    }
}
