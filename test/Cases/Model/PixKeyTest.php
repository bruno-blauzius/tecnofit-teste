<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Model;

use App\Model\PixKey;
use Hyperf\DbConnection\Db;
use HyperfTest\HttpTestCase;

/**
 * @internal
 * @coversNothing
 */
class PixKeyTest extends HttpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->truncateTables();
        $this->createTestAccount();
    }

    protected function tearDown(): void
    {
        $this->truncateTables();
        parent::tearDown();
    }

    private function truncateTables(): void
    {
        Db::statement('SET FOREIGN_KEY_CHECKS=0;');
        Db::table('pix_keys')->truncate();
        Db::table('account')->truncate();
        Db::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function createTestAccount(): void
    {
        Db::table('account')->insert([
            'id' => 'test-account-id',
            'name' => 'Test Account',
            'balance' => 1000.00,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function testCreatePixKeyWithValidCpf()
    {
        $pixKey = new PixKey();
        $pixKey->account_id = 'test-account-id';
        $pixKey->key_type = 'cpf';
        $pixKey->key_value = '123.456.789-09';
        $pixKey->status = 'active';

        $this->assertTrue($pixKey->save());
        $this->assertNotEmpty($pixKey->id);
        $this->assertEquals('cpf', $pixKey->key_type);
    }

    public function testCreatePixKeyWithInvalidCpf()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Chave PIX inválida para o tipo "cpf"');

        $pixKey = new PixKey();
        $pixKey->account_id = 'test-account-id';
        $pixKey->key_type = 'cpf';
        $pixKey->key_value = '123.456.789-00';
        $pixKey->status = 'active';
        $pixKey->save();
    }

    public function testCreatePixKeyWithValidCnpj()
    {
        $pixKey = new PixKey();
        $pixKey->account_id = 'test-account-id';
        $pixKey->key_type = 'cnpj';
        $pixKey->key_value = '11.222.333/0001-81';
        $pixKey->status = 'active';

        $this->assertTrue($pixKey->save());
        $this->assertNotEmpty($pixKey->id);
    }

    public function testCreatePixKeyWithInvalidCnpj()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Chave PIX inválida para o tipo "cnpj"');

        $pixKey = new PixKey();
        $pixKey->account_id = 'test-account-id';
        $pixKey->key_type = 'cnpj';
        $pixKey->key_value = '11.222.333/0001-00';
        $pixKey->status = 'active';
        $pixKey->save();
    }

    public function testCreatePixKeyWithValidEmail()
    {
        $pixKey = new PixKey();
        $pixKey->account_id = 'test-account-id';
        $pixKey->key_type = 'email';
        $pixKey->key_value = 'test@example.com';
        $pixKey->status = 'active';

        $this->assertTrue($pixKey->save());
        $this->assertEquals('test@example.com', $pixKey->key_value);
    }

    public function testCreatePixKeyWithInvalidEmail()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Chave PIX inválida para o tipo "email"');

        $pixKey = new PixKey();
        $pixKey->account_id = 'test-account-id';
        $pixKey->key_type = 'email';
        $pixKey->key_value = 'invalid-email';
        $pixKey->status = 'active';
        $pixKey->save();
    }

    public function testCreatePixKeyWithValidPhone()
    {
        $pixKey = new PixKey();
        $pixKey->account_id = 'test-account-id';
        $pixKey->key_type = 'phone';
        $pixKey->key_value = '+5511999999999';
        $pixKey->status = 'active';

        $this->assertTrue($pixKey->save());
        $this->assertEquals('+5511999999999', $pixKey->key_value);
    }

    public function testCreatePixKeyWithValidPhoneWithoutFormatting()
    {
        $pixKey = new PixKey();
        $pixKey->account_id = 'test-account-id';
        $pixKey->key_type = 'phone';
        $pixKey->key_value = '11999999999';
        $pixKey->status = 'active';

        $this->assertTrue($pixKey->save());
    }

    public function testCreatePixKeyWithInvalidPhone()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Chave PIX inválida para o tipo "phone"');

        $pixKey = new PixKey();
        $pixKey->account_id = 'test-account-id';
        $pixKey->key_type = 'phone';
        $pixKey->key_value = '123';
        $pixKey->status = 'active';
        $pixKey->save();
    }

    public function testCreatePixKeyWithValidRandomKey()
    {
        $pixKey = new PixKey();
        $pixKey->account_id = 'test-account-id';
        $pixKey->key_type = 'random';
        $pixKey->key_value = '123e4567-e89b-12d3-a456-426614174000';
        $pixKey->status = 'active';

        $this->assertTrue($pixKey->save());
        $this->assertEquals('random', $pixKey->key_type);
    }

    public function testCreatePixKeyWithInvalidRandomKey()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Chave PIX inválida para o tipo "random"');

        $pixKey = new PixKey();
        $pixKey->account_id = 'test-account-id';
        $pixKey->key_type = 'random';
        $pixKey->key_value = 'not-a-uuid';
        $pixKey->status = 'active';
        $pixKey->save();
    }

    public function testCreatePixKeyWithoutKeyType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tipo e valor da chave PIX são obrigatórios');

        $pixKey = new PixKey();
        $pixKey->account_id = 'test-account-id';
        $pixKey->key_value = '123.456.789-09';
        $pixKey->status = 'active';
        $pixKey->save();
    }

    public function testCreatePixKeyWithoutKeyValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tipo e valor da chave PIX são obrigatórios');

        $pixKey = new PixKey();
        $pixKey->account_id = 'test-account-id';
        $pixKey->key_type = 'cpf';
        $pixKey->status = 'active';
        $pixKey->save();
    }

    public function testPixKeyGeneratesUuidAutomatically()
    {
        $pixKey = new PixKey();
        $pixKey->account_id = 'test-account-id';
        $pixKey->key_type = 'email';
        $pixKey->key_value = 'auto-uuid@example.com';
        $pixKey->status = 'active';
        $pixKey->save();

        $this->assertNotEmpty($pixKey->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $pixKey->id
        );
    }

    public function testPixKeyRelationshipWithAccount()
    {
        $pixKey = new PixKey();
        $pixKey->account_id = 'test-account-id';
        $pixKey->key_type = 'email';
        $pixKey->key_value = 'relationship@example.com';
        $pixKey->status = 'active';
        $pixKey->save();

        $account = $pixKey->account;

        $this->assertNotNull($account);
        $this->assertEquals('test-account-id', $account->id);
        $this->assertEquals('Test Account', $account->name);
    }

    public function testPixKeySoftDelete()
    {
        $pixKey = new PixKey();
        $pixKey->account_id = 'test-account-id';
        $pixKey->key_type = 'email';
        $pixKey->key_value = 'soft-delete@example.com';
        $pixKey->status = 'active';
        $pixKey->save();

        $id = $pixKey->id;

        // Soft delete
        $pixKey->delete();

        // Verifica que foi soft deleted
        $deletedKey = PixKey::withTrashed()->find($id);
        $this->assertNotNull($deletedKey);
        $this->assertNotNull($deletedKey->deleted_at);

        // Verifica que não aparece em consultas normais
        $normalKey = PixKey::find($id);
        $this->assertNull($normalKey);
    }

    public function testUpdatePixKeyValue()
    {
        $pixKey = new PixKey();
        $pixKey->account_id = 'test-account-id';
        $pixKey->key_type = 'email';
        $pixKey->key_value = 'old@example.com';
        $pixKey->status = 'active';
        $pixKey->save();

        // Atualiza para um novo email válido
        $pixKey->key_value = 'new@example.com';
        $this->assertTrue($pixKey->save());
        $this->assertEquals('new@example.com', $pixKey->key_value);
    }

    public function testUpdatePixKeyWithInvalidValue()
    {
        $this->expectException(\InvalidArgumentException::class);

        $pixKey = new PixKey();
        $pixKey->account_id = 'test-account-id';
        $pixKey->key_type = 'email';
        $pixKey->key_value = 'valid@example.com';
        $pixKey->save();

        // Tenta atualizar para um email inválido
        $pixKey->key_value = 'invalid-email';
        $pixKey->save();
    }

    public function testValidateCpfWithoutFormatting()
    {
        $this->assertTrue(PixKey::validateKeyValue('cpf', '12345678909'));
    }

    public function testValidateCpfWithFormatting()
    {
        $this->assertTrue(PixKey::validateKeyValue('cpf', '123.456.789-09'));
    }

    public function testValidateCpfRejectsSameDigits()
    {
        $this->assertFalse(PixKey::validateKeyValue('cpf', '11111111111'));
        $this->assertFalse(PixKey::validateKeyValue('cpf', '000.000.000-00'));
    }

    public function testValidateCnpjWithoutFormatting()
    {
        $this->assertTrue(PixKey::validateKeyValue('cnpj', '11222333000181'));
    }

    public function testValidateCnpjWithFormatting()
    {
        $this->assertTrue(PixKey::validateKeyValue('cnpj', '11.222.333/0001-81'));
    }

    public function testValidateEmailVariousFormats()
    {
        $this->assertTrue(PixKey::validateKeyValue('email', 'test@example.com'));
        $this->assertTrue(PixKey::validateKeyValue('email', 'user.name@domain.co.uk'));
        $this->assertTrue(PixKey::validateKeyValue('email', 'test+tag@example.com'));
        $this->assertFalse(PixKey::validateKeyValue('email', 'invalid@'));
        $this->assertFalse(PixKey::validateKeyValue('email', '@invalid.com'));
    }

    public function testValidatePhoneVariousFormats()
    {
        $this->assertTrue(PixKey::validateKeyValue('phone', '11999999999'));
        $this->assertTrue(PixKey::validateKeyValue('phone', '+5511999999999'));
        $this->assertTrue(PixKey::validateKeyValue('phone', '(11) 99999-9999'));
        $this->assertFalse(PixKey::validateKeyValue('phone', '123'));
        $this->assertFalse(PixKey::validateKeyValue('phone', '12345678901234'));
    }

    public function testValidateRandomKeyUuidFormat()
    {
        $this->assertTrue(PixKey::validateKeyValue('random', '123e4567-e89b-12d3-a456-426614174000'));
        $this->assertTrue(PixKey::validateKeyValue('random', 'a1b2c3d4-e5f6-7890-abcd-ef1234567890'));
        $this->assertFalse(PixKey::validateKeyValue('random', 'not-a-uuid'));
        $this->assertFalse(PixKey::validateKeyValue('random', '123e4567e89b12d3a456426614174000'));
    }

    public function testValidateKeyValueInvalidType()
    {
        $this->assertFalse(PixKey::validateKeyValue('invalid_type', 'any_value'));
    }
}
