<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use Hyperf\Database\Model\Events\Creating;
use Hyperf\Database\Model\Events\Saving;
use Ramsey\Uuid\Uuid;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $account_id
 */
class User extends Model
{
    protected ?string $table = 'users';

    protected string $primaryKey = 'id';

    protected string $keyType = 'string';

    public bool $incrementing = false;

    protected array $fillable = [
        'id',
        'name',
        'email',
        'password',
        'account_id',
    ];

    protected array $hidden = [
        'password',
    ];

    protected array $casts = [];

    /**
     * Evento disparado antes de criar o registro
     */
    public function creating(Creating $event)
    {
        $model = $event->getModel();

        // Gera UUID se não existir
        if (!isset($model->id) || empty($model->id)) {
            $model->id = Uuid::uuid4()->toString();
        }
    }

    /**
     * Evento disparado antes de salvar (create ou update)
     */
    public function saving(Saving $event)
    {
        $model = $event->getModel();

        // Valida o email
        if (isset($model->email) && !filter_var($model->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('O e-mail fornecido não é válido.');
        }

        // Hash da senha com SHA256 (apenas se a senha foi modificada)
        if ($model->isDirty('password') && !empty($model->password)) {
            // Verifica se já não está em hash (64 caracteres = SHA256)
            if (strlen($model->password) !== 64) {
                $model->password = hash('sha256', $model->password);
            }
        }
    }

    /**
     * Relacionamento 1:1 com Account
     */
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    /**
     * Verifica se a senha fornecida corresponde à senha armazenada
     */
    public function verifyPassword(string $password): bool
    {
        return hash('sha256', $password) === $this->password;
    }
}
