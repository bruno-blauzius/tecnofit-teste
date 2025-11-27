<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\Database\Model\Events\Creating;
use Hyperf\Database\Model\Events\Saving;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Traits\HasContainer;
use Ramsey\Uuid\Uuid;

/**
 * @property string $id
 * @property string $account_id
 * @property string $key_type
 * @property string $key_value
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class PixKey extends Model
{
    use HasContainer;
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'pix_keys';

    protected string $primaryKey = 'id';

    protected string $keyType = 'string';

    public bool $incrementing = false;

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [
        'id',
        'account_id',
        'key_type',
        'key_value',
        'status',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = [
        'id' => 'string',
        'account_id' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function creating(Creating $event): void
    {
        $model = $event->getModel();

        if ($this->shouldGenerateId($model)) {
            $model->id = Uuid::uuid4()->toString();
        }
    }

    public function saving(Saving $event): void
    {
        $model = $event->getModel();

        $this->validateRequiredFields($model);
        $this->validatePixKeyFormat($model);
    }

    private function shouldGenerateId($model): bool
    {
        return !isset($model->id) || empty($model->id);
    }

    private function validateRequiredFields($model): void
    {
        if (empty($model->key_type) || empty($model->key_value)) {
            throw new \InvalidArgumentException('Tipo e valor da chave PIX são obrigatórios.');
        }
    }

    private function validatePixKeyFormat($model): void
    {
        if (!self::validateKeyValue($model->key_type, $model->key_value)) {
            throw new \InvalidArgumentException(
                sprintf('Chave PIX inválida para o tipo "%s".', $model->key_type)
            );
        }
    }

    /**
     * Relacionamento com Account
     */
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    public static function validateKeyValue(string $keyType, string $keyValue): bool
    {
        return match ($keyType) {
            'cpf' => self::isValidCpf($keyValue),
            'cnpj' => self::isValidCnpj($keyValue),
            'email' => self::isValidEmail($keyValue),
            'phone' => self::isValidPhone($keyValue),
            'random' => self::isValidRandomKey($keyValue),
            default => false,
        };
    }

    private static function isValidCpf(string $cpf): bool
    {
        $cleanCpf = self::extractNumbers($cpf);

        if (!self::hasValidCpfLength($cleanCpf)) {
            return false;
        }

        return self::validateCpfCheckDigits($cleanCpf);
    }

    private static function extractNumbers(string $value): string
    {
        return preg_replace('/[^0-9]/', '', $value);
    }

    private static function hasValidCpfLength(string $cpf): bool
    {
        return strlen($cpf) === 11 && !preg_match('/(\d)\1{10}/', $cpf);
    }

    private static function validateCpfCheckDigits(string $cpf): bool
    {
        for ($position = 9; $position < 11; $position++) {
            $digit = self::calculateCpfCheckDigit($cpf, $position);

            if ($cpf[$position] != $digit) {
                return false;
            }
        }

        return true;
    }

    private static function calculateCpfCheckDigit(string $cpf, int $position): int
    {
        $sum = 0;

        for ($index = 0; $index < $position; $index++) {
            $sum += $cpf[$index] * (($position + 1) - $index);
        }

        return ((10 * $sum) % 11) % 10;
    }

    private static function isValidCnpj(string $cnpj): bool
    {
        $cleanCnpj = self::extractNumbers($cnpj);

        if (!self::hasValidCnpjLength($cleanCnpj)) {
            return false;
        }

        return self::validateCnpjCheckDigits($cleanCnpj);
    }

    private static function hasValidCnpjLength(string $cnpj): bool
    {
        return strlen($cnpj) === 14 && !preg_match('/(\d)\1{13}/', $cnpj);
    }

    private static function validateCnpjCheckDigits(string $cnpj): bool
    {
        $firstDigit = self::calculateCnpjCheckDigit($cnpj, 12);
        if ($firstDigit != $cnpj[12]) {
            return false;
        }

        $secondDigit = self::calculateCnpjCheckDigit($cnpj, 13);
        return $secondDigit == $cnpj[13];
    }

    private static function calculateCnpjCheckDigit(string $cnpj, int $length): int
    {
        $numbers = substr($cnpj, 0, $length);
        $sum = 0;
        $position = $length - 7;

        for ($index = $length; $index >= 1; $index--) {
            $sum += $numbers[$length - $index] * $position--;

            if ($position < 2) {
                $position = 9;
            }
        }

        $remainder = $sum % 11;
        return $remainder < 2 ? 0 : 11 - $remainder;
    }

    private static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private static function isValidPhone(string $phone): bool
    {
        $cleanPhone = self::extractNumbers($phone);
        return self::hasValidPhoneLength($cleanPhone);
    }

    private static function hasValidPhoneLength(string $phone): bool
    {
        $length = strlen($phone);
        return $length >= 10 && $length <= 13;
    }

    private static function isValidRandomKey(string $randomKey): bool
    {
        return self::matchesUuidPattern($randomKey);
    }

    private static function matchesUuidPattern(string $value): bool
    {
        $uuidPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        return preg_match($uuidPattern, $value) === 1;
    }
}
