<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use Hyperf\Database\Model\Events\Creating;
use Ramsey\Uuid\Uuid;

/**
 * @property string $id
 * @property string $name
 * @property string $balance
 */
class Account extends Model
{
    protected ?string $table = 'account';

    protected string $primaryKey = 'id';

    protected string $keyType = 'string';

    public bool $incrementing = false;

    protected array $fillable = [
        'id',
        'name',
        'balance',
    ];

    protected array $casts = [
        'balance' => 'decimal:2',
    ];

    public function creating(Creating $event)
    {
        $model = $event->getModel();
        if (!isset($model->id) || empty($model->id)) {
            $model->id = Uuid::uuid4()->toString();
        }
    }

    public function withdraws()
    {
        return $this->hasMany(AccountWithdraw::class, 'account_id', 'id');
    }

    /**
     * Relacionamento 1:1 com User
     */
    public function user()
    {
        return $this->hasOne(User::class, 'account_id', 'id');
    }
}
