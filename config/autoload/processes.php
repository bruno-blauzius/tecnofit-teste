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
use Hyperf\Crontab\Process\CrontabDispatcherProcess;

/**
 * This file is part of Hyperf.
 *
 * @see     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

use function Hyperf\Support\env;

$processes = [];

// Se for o container de crontab, carrega apenas o CrontabDispatcherProcess
if (env('HYPERF_CONFIG_MODE') === 'crontab') {
    $processes[] = CrontabDispatcherProcess::class;
}

return $processes;
