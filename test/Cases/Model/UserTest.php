<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Model;

use App\Model\User;
use App\Model\Account;
use Hyperf\Testing\TestCase;
use Hyperf\DbConnection\Db;

class UserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Db::statement('SET FOREIGN_KEY_CHECKS=0');
        if (\Hyperf\Database\Schema\Schema::hasTable('users')) {
            Db::table('users')->delete();
        }
        if (\Hyperf\Database\Schema\Schema::hasTable('account')) {
            Db::table('account')->delete();
        }
        Db::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function testUserCanBeCreated()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);

        $this->assertNotNull($user);
        $this->assertNotNull($user->id);
        $this->assertSame('John Doe', $user->name);
        $this->assertSame('john@example.com', $user->email);
    }

    public function testUserGeneratesUuidOnCreation()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);

        $this->assertNotNull($user->id);
        $this->assertSame(36, strlen($user->id)); // UUID = 36 caracteres
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $user->id
        );
    }

    public function testUserHashesPasswordWithSha256OnCreate()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);

        $this->assertNotSame('secret123', $user->password);
        $this->assertSame(64, strlen($user->password)); // SHA256 = 64 caracteres
        $this->assertSame(hash('sha256', 'secret123'), $user->password);
    }

    public function testUserHashesPasswordWithSha256OnUpdate()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);

        $oldPassword = $user->password;

        // Atualiza a senha
        $user->password = 'newsecret456';
        $user->save();

        $this->assertNotSame($oldPassword, $user->password);
        $this->assertNotSame('newsecret456', $user->password);
        $this->assertSame(hash('sha256', 'newsecret456'), $user->password);
    }

    public function testUserDoesNotRehashAlreadyHashedPassword()
    {
        $hashedPassword = hash('sha256', 'secret123');

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => $hashedPassword,
        ]);

        // Senha já estava em hash, não deve mudar
        $this->assertSame($hashedPassword, $user->password);
    }

    public function testUserValidatesEmailFormat()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('O e-mail fornecido não é válido.');

        User::create([
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'secret123',
        ]);
    }

    public function testUserAcceptsValidEmail()
    {
        $validEmails = [
            'john@example.com',
            'jane.doe@company.co.uk',
            'test+tag@domain.com',
            'user123@test.org',
        ];

        foreach ($validEmails as $email) {
            $user = User::create([
                'name' => 'Test User',
                'email' => $email,
                'password' => 'secret123',
            ]);

            $this->assertSame($email, $user->email);

            // Limpa para próxima iteração
            $user->delete();
        }
    }

    public function testUserHidesPasswordInArray()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);

        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('email', $array);
    }

    public function testUserVerifyPasswordMethod()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);

        $this->assertTrue($user->verifyPassword('secret123'));
        $this->assertFalse($user->verifyPassword('wrongpassword'));
    }

    public function testUserBelongsToAccount()
    {
        $account = Account::create([
            'name' => 'Test Account',
            'balance' => 1000.00,
        ]);

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'account_id' => $account->id,
        ]);

        $this->assertNotNull($user->account);
        $this->assertInstanceOf(Account::class, $user->account);
        $this->assertSame($account->id, $user->account->id);
    }

    public function testUserCanExistWithoutAccount()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'account_id' => null,
        ]);

        $this->assertNull($user->account_id);
        $this->assertNull($user->account);
    }

    public function testAccountHasOneUser()
    {
        $account = Account::create([
            'name' => 'Test Account',
            'balance' => 1000.00,
        ]);

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'account_id' => $account->id,
        ]);

        $account->refresh();

        $this->assertNotNull($account->user);
        $this->assertInstanceOf(User::class, $account->user);
        $this->assertSame($user->id, $account->user->id);
    }

    public function testUserEmailMustBeUnique()
    {
        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);

        $this->expectException(\PDOException::class);

        User::create([
            'name' => 'Jane Doe',
            'email' => 'john@example.com', // Email duplicado
            'password' => 'secret456',
        ]);
    }

    public function testUserCanUpdateNameAndEmail()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);

        $user->name = 'Jane Smith';
        $user->email = 'jane@example.com';
        $user->save();

        $this->assertSame('Jane Smith', $user->name);
        $this->assertSame('jane@example.com', $user->email);
    }

    public function testUserValidatesEmailOnUpdate()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('O e-mail fornecido não é válido.');

        $user->email = 'invalid-email';
        $user->save();
    }

    public function testUserAccountIdCanBeNull()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'account_id' => null,
        ]);

        $this->assertNull($user->account_id);

        // Pode definir depois
        $account = Account::create([
            'name' => 'Test Account',
            'balance' => 500.00,
        ]);

        $user->account_id = $account->id;
        $user->save();

        $this->assertSame($account->id, $user->account_id);
    }
}
