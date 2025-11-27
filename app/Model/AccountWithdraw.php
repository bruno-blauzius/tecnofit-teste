<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Model;

use Hyperf\Database\Model\Events\Creating;
use Hyperf\DbConnection\Model\Model;
use Ramsey\Uuid\Uuid;

/**
 * @property string $id
 * @property string $account_id
 * @property string $method
 * @property string $amount
 * @property bool $scheduled
 * @property null|string $scheduled_for
 * @property bool $done
 * @property bool $error
 * @property null|string $error_reason
 */
class AccountWithdraw extends Model
{
    public bool $incrementing = false;

    protected ?string $table = 'account_withdraw';

    protected string $primaryKey = 'id';

    protected string $keyType = 'string';

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
        'amount' => 'decimal:2',
        'scheduled' => 'bool',
        'done' => 'bool',
        'error' => 'bool',
        'scheduled_for' => 'datetime',
    ];

    public function creating(Creating $event)
    {
        $model = $event->getModel();
        if (! isset($model->id) || empty($model->id)) {
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
