<?php

declare(strict_types=1);

namespace HyperfTest\Cases\UseCase\Account;

use App\Model\Account;
use App\UseCase\Account\CreateAccountRequest;
use App\UseCase\Account\CreateAccountUseCase;
use PHPUnit\Framework\TestCase;
use Hyperf\DbConnection\Db;

class CreateAccountUseCaseTest extends TestCase
{
    private CreateAccountUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        if (\Hyperf\Database\Schema\Schema::hasTable('account')) {
            Db::table('account')->delete();
        }
        $this->useCase = new CreateAccountUseCase(new \App\Model\AccountTransactionHistory());
    }

    public function testExecuteCreatesAccountWithValidData()
    {
        $request = new CreateAccountRequest(
            name: 'Test Account',
            balance: 1000.50
        );

        $result = $this->useCase->execute($request);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertSame(1000.50, $result['balance']);
        $this->assertArrayHasKey('created_at', $result);
        $this->assertArrayHasKey('updated_at', $result);

        $this->assertDatabaseHas('account', [

            'balance' => 1000.50,
        ]);
    }

    public function testExecuteCreatesAccountWithZeroBalance()
    {
        $request = new CreateAccountRequest(
            name: 'Test Account',
            balance: 0
        );

        $result = $this->useCase->execute($request);

        $this->assertSame(0.0, $result['balance']);
        $this->assertDatabaseHas('account', [

            'balance' => 0,
        ]);
    }

    public function testExecuteGeneratesUuidForId()
    {
        $request = new CreateAccountRequest(
            name: 'Test Account',
            balance: 100
        );

        $result = $this->useCase->execute($request);

        $this->assertIsString($result['id']);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $result['id']
        );
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
