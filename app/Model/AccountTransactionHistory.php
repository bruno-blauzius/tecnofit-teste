<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use Hyperf\Database\Model\Events\Creating;
use Ramsey\Uuid\Uuid;

/**
 * @property string $id
 * @property string $account_id
 * @property string $type
 * @property float $amount
 * @property float $balance_before
 * @property float $balance_after
 * @property string|null $description
 * @property string|null $reference_id
 * @property string|null $reference_type
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AccountTransactionHistory extends Model
{
    protected ?string $table = 'account_transaction_history';

    protected string $primaryKey = 'id';

    protected string $keyType = 'string';

    public bool $incrementing = false;

    protected array $fillable = [
        'id',
        'account_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'reference_id',
        'reference_type',
    ];

    protected array $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creating(Creating $event): void
    {
        $model = $event->getModel();
        if (!isset($model->id) || empty($model->id)) {
            $model->id = Uuid::uuid4()->toString();
        }
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }
}
