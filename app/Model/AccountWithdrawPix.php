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
 * @property string $account_withdraw_id
 * @property string $type
 * @property string $key_value
 */
class AccountWithdrawPix extends Model
{
    public bool $incrementing = false;

    protected ?string $table = 'account_withdraw_pix';

    protected string $primaryKey = 'id';

    protected string $keyType = 'string';

    protected array $fillable = [
        'id',
        'account_withdraw_id',
        'type',
        'key_value',
    ];

    public function creating(Creating $event): void
    {
        $model = $event->getModel();
        if (! isset($model->id) || empty($model->id)) {
            $model->id = Uuid::uuid4()->toString();
        }
    }

    public function withdraw()
    {
        return $this->belongsTo(AccountWithdraw::class, 'account_withdraw_id', 'id');
    }
}
