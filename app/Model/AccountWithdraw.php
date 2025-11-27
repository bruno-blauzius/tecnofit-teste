<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use Hyperf\Database\Model\Events\Creating;
use Ramsey\Uuid\Uuid;

/**
 * @property string $id
 * @property string $account_id
 * @property string $method
 * @property string $amount
 * @property bool $scheduled
 * @property string|null $scheduled_for
 * @property bool $done
 * @property bool $error
 * @property string|null $error_reason
 */
class AccountWithdraw extends Model
{
    protected ?string $table = 'account_withdraw';

    protected string $primaryKey = 'id';

    protected string $keyType = 'string';

    public bool $incrementing = false;

    protected array $fillable = [
        'id',
        'account_id',
        'method',
        'amount',
        'scheduled',
        'scheduled_for',
        'done',
        'error',
        'error_reason',
    ];

    protected array $casts = [
        'amount'        => 'decimal:2',
        'scheduled'     => 'bool',
        'done'          => 'bool',
        'error'         => 'bool',
        'scheduled_for' => 'datetime',
    ];

    public function creating(Creating $event)
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

    public function pix()
    {
        return $this->hasOne(AccountWithdrawPix::class, 'account_withdraw_id', 'id');
    }
}
