<?php

declare(strict_types=1);

namespace App\UseCase\Account\Exception;

use App\Exception\BusinessException;
use App\Constants\ErrorCode;

final class PixKeyNotFoundException extends BusinessException
{
    public function __construct(string $message = 'Chave PIX não encontrada')
    {
        parent::__construct(ErrorCode::SERVER_ERROR, $message);
    }
}
