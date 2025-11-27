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

namespace App\UseCase\Account\Exception;

use App\Constants\ErrorCode;
use App\Exception\BusinessException;

final class PixKeyNotFoundException extends BusinessException
{
    public function __construct(string $message = 'Chave PIX não encontrada')
    {
        parent::__construct(ErrorCode::SERVER_ERROR, $message);
    }
}
