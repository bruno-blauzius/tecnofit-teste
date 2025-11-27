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
 * @property string $name
 * @property string $balance
 */
class Account extends Model
{
    public bool $incrementing = false;

    protected ?string $table = 'account';

    protected string $primaryKey = 'id';

    protected string $keyType = 'string';

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
        if (! isset($model->id) || empty($model->id)) {
            $model->id = Uuid::uuid4()->toString();
        }
    }

    public function withdraws()
    {
        return $this->hasMany(AccountWithdraw::class, 'account_id', 'id');
    }

    /**
     * Relacionamento 1:1 com User.
     */
    public function user()
    {
        return $this->hasOne(User::class, 'account_id', 'id');
    }
}
