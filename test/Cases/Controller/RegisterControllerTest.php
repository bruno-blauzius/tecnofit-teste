<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Controller;

use App\Model\User;
use App\Model\Account;
use Hyperf\Testing\TestCase;
use Hyperf\DbConnection\Db;

class RegisterControllerTest extends TestCase
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

    public function testRegisterCreatesUserSuccessfully()
    {
        $response = $this->json('/api/v1/public/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'confirm_password' => 'secret123',
        ]);

        $response->assertStatus(201);
        $result = $response->json();

        $this->assertArrayHasKey('data', $result);
        $this->assertSame('John Doe', $result['data']['name']);
        $this->assertSame('john@example.com', $result['data']['email']);
        $this->assertArrayHasKey('id', $result['data']);
        $this->assertArrayNotHasKey('password', $result['data']);
    }

    public function testRegisterValidatesRequiredFields()
    {
        $response = $this->json('/api/v1/public/register', []);

        $response->assertStatus(422);
        $result = $response->json();

        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('name', $result['errors']);
        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertArrayHasKey('password', $result['errors']);
        $this->assertArrayHasKey('confirm_password', $result['errors']);
    }

    public function testRegisterValidatesEmailFormat()
    {
        $response = $this->json('/api/v1/public/register', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'secret123',
            'confirm_password' => 'secret123',
        ]);

        $response->assertStatus(422);
        $result = $response->json();

        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('email', $result['errors']);
    }

    public function testRegisterValidatesPasswordMinimumLength()
    {
        $response = $this->json('/api/v1/public/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => '12345',
            'confirm_password' => '12345',
        ]);

        $response->assertStatus(422);
        $result = $response->json();

        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('password', $result['errors']);
    }

    public function testRegisterValidatesPasswordConfirmation()
    {
        $response = $this->json('/api/v1/public/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'confirm_password' => 'different123',
        ]);

        $response->assertStatus(422);
        $result = $response->json();

        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('confirm_password', $result['errors']);
    }

    public function testRegisterValidatesUniqueEmail()
    {
        // Cria primeiro usuário
        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);

        // Tenta criar segundo usuário com mesmo email
        $response = $this->json('/api/v1/public/register', [
            'name' => 'Jane Doe',
            'email' => 'john@example.com',
            'password' => 'secret456',
            'confirm_password' => 'secret456',
        ]);

        $response->assertStatus(422);
        $result = $response->json();

        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('email', $result['errors']);
    }

    public function testRegisterHashesPasswordWithSha256()
    {
        $response = $this->json('/api/v1/public/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'confirm_password' => 'secret123',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'john@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame(64, strlen($user->password)); // SHA256 = 64 caracteres
        $this->assertSame(hash('sha256', 'secret123'), $user->password);
    }

    public function testRegisterCanLinkUserToAccount()
    {
        $response = $this->json('/api/v1/public/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'confirm_password' => 'secret123',
        ]);

        $response->assertStatus(201);
        $result = $response->json();

        // Verifica que account_id foi criado automaticamente
        $this->assertNotNull($result['data']['account_id']);
        $this->assertArrayHasKey('account', $result['data']);
        $this->assertEquals(0.0, $result['data']['account']['balance']);

        // Verifica relacionamento
        $user = User::find($result['data']['id']);
        $this->assertNotNull($user->account);
        $this->assertSame($result['data']['account_id'], $user->account->id);
        $this->assertEquals(0.0, (float) $user->account->balance);
    }

    public function testRegisterValidatesEmailMaxLength()
    {
        $response = $this->json('/api/v1/public/register', [
            'name' => 'John Doe',
            'email' => str_repeat('a', 250) . '@example.com', // > 255 caracteres
            'password' => 'secret123',
            'confirm_password' => 'secret123',
        ]);

        $response->assertStatus(422);
        $result = $response->json();

        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('email', $result['errors']);
    }

    public function testRegisterValidatesNameMaxLength()
    {
        $response = $this->json('/api/v1/public/register', [
            'name' => str_repeat('a', 256), // > 255 caracteres
            'email' => 'john@example.com',
            'password' => 'secret123',
            'confirm_password' => 'secret123',
        ]);

        $response->assertStatus(422);
        $result = $response->json();

        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('name', $result['errors']);
    }
}
