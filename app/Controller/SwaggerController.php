<?php

declare(strict_types=1);

namespace App\Controller;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Tecnofit API',
    description: 'API para gerenciamento de contas, saques e autenticação de usuários'
)]
#[OA\Server(
    url: 'http://localhost:9501',
    description: 'Servidor local de desenvolvimento'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Erro ao processar requisição'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            example: ['field' => ['Mensagem de erro']]
        ),
    ]
)]
#[OA\Schema(
    schema: 'ValidationErrorResponse',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            example: [
                'email' => ['O campo e-mail é obrigatório.'],
                'password' => ['O campo senha deve ter no mínimo 6 caracteres.']
            ]
        ),
    ]
)]
class SwaggerController
{
}
