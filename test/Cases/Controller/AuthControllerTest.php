<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Controller;

use App\Helper\JwtHelper;
use App\Model\User;
use Hyperf\Testing\TestCase;
use Hyperf\DbConnection\Db;

class AuthControllerTest extends TestCase
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

    public function testAuthenticateSuccessfully()
    {
        // Cria um usuário de teste
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Faz a requisição de autenticação
        $response = $this->json('/api/v1/public/auth', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Verifica o status da resposta
        $response->assertStatus(200);

        // Verifica a estrutura da resposta
        $result = $response->json();
        $this->assertTrue($result['success']);
        $this->assertSame('Login realizado com sucesso!', $result['message']);
        $this->assertArrayHasKey('token', $result['data']);
        $this->assertArrayHasKey('type', $result['data']);
        $this->assertArrayHasKey('expires_in', $result['data']);
        $this->assertArrayHasKey('refresh_token', $result['data']);        // Verifica o tipo do token
        $this->assertSame('Bearer', $result['data']['type']);

        // Verifica se o refresh_token existe e é null ou string
        $this->assertTrue(
            $result['data']['refresh_token'] === null || is_string($result['data']['refresh_token']),
            'refresh_token deve ser null ou string'
        );

        // Verifica se o token é válido
        $this->assertTrue(JwtHelper::validate($result['data']['token']));

        // Decodifica o token e verifica os dados
        $decoded = JwtHelper::decode($result['data']['token']);
        $this->assertSame($user->id, $decoded->sub);
        $this->assertSame($user->email, $decoded->email);
    }

    public function testAuthenticateWithInvalidEmail()
    {
        // Faz a requisição com email inexistente
        $response = $this->json('/api/v1/public/auth', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        // Verifica o status da resposta
        $response->assertStatus(401);

        // Verifica a estrutura da resposta
        $result = $response->json();
        $this->assertFalse($result['success']);
        $this->assertSame('Credenciais inválidas', $result['message']);
    }

    public function testAuthenticateWithInvalidPassword()
    {
        // Cria um usuário de teste
        User::create([
            'name' => 'Test User',
            'email' => 'test2@example.com',
            'password' => 'correctpassword',
        ]);

        // Faz a requisição com senha incorreta
        $response = $this->json('/api/v1/public/auth', [
            'email' => 'test2@example.com',
            'password' => 'wrongpassword',
        ]);

        // Verifica o status da resposta
        $response->assertStatus(401);

        // Verifica a estrutura da resposta
        $result = $response->json();
        $this->assertFalse($result['success']);
        $this->assertSame('Credenciais inválidas', $result['message']);
    }

    public function testAuthenticateValidatesRequiredEmail()
    {
        // Faz a requisição sem email
        $response = $this->json('/api/v1/public/auth', [
            'password' => 'password123',
        ]);

        // Verifica o status da resposta
        $response->assertStatus(422);
    }

    public function testAuthenticateValidatesEmailFormat()
    {
        // Faz a requisição com email inválido
        $response = $this->json('/api/v1/public/auth', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        // Verifica o status da resposta
        $response->assertStatus(422);
    }

    public function testAuthenticateValidatesRequiredPassword()
    {
        // Faz a requisição sem senha
        $response = $this->json('/api/v1/public/auth', [
            'email' => 'test@example.com',
        ]);

        // Verifica o status da resposta
        $response->assertStatus(422);
    }

    public function testAuthenticateValidatesPasswordMinLength()
    {
        // Faz a requisição com senha muito curta
        $response = $this->json('/api/v1/public/auth', [
            'email' => 'test@example.com',
            'password' => '12345',
        ]);

        // Verifica o status da resposta
        $response->assertStatus(422);
    }

    public function testJwtTokenExpirationTime()
    {
        // Cria um usuário de teste
        User::create([
            'name' => 'Test User',
            'email' => 'test3@example.com',
            'password' => 'password123',
        ]);

        // Faz a requisição de autenticação
        $response = $this->json('/api/v1/public/auth', [
            'email' => 'test3@example.com',
            'password' => 'password123',
        ]);

        $result = $response->json();
        $decoded = JwtHelper::decode($result['data']['token']);

        // Verifica se o token tem tempo de expiração
        $this->assertObjectHasProperty('exp', $decoded);
        $this->assertObjectHasProperty('iat', $decoded);

        // Verifica se a diferença entre exp e iat é o tempo configurado (3600 segundos = 1 hora)
        $expectedExpiration = 3600;
        $this->assertSame($expectedExpiration, $decoded->exp - $decoded->iat);
    }

    public function testJwtTokenContainsCorrectIssuerAndAudience()
    {
        // Cria um usuário de teste
        User::create([
            'name' => 'Test User',
            'email' => 'test4@example.com',
            'password' => 'password123',
        ]);

        // Faz a requisição de autenticação
        $response = $this->json('/api/v1/public/auth', [
            'email' => 'test4@example.com',
            'password' => 'password123',
        ]);

        $result = $response->json();
        $decoded = JwtHelper::decode($result['data']['token']);

        // Verifica issuer e audience
        $this->assertSame('tecnofit-api', $decoded->iss);
        $this->assertSame('tecnofit-app', $decoded->aud);
    }
}
