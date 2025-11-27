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
use App\Crontab\ProcessScheduledWithdrawsCrontab;
use Hyperf\Crontab\Crontab;

return [
    'enable' => true,
    'crontab' => [
        (new Crontab())
            ->setName('process_scheduled_withdraws')
            ->setRule('*/1 * * * *') // a cada 1 minuto
            ->setCallback([ProcessScheduledWithdrawsCrontab::class, 'handle'])
            ->setMemo('Processa saques agendados automaticamente'),
    ],
];
