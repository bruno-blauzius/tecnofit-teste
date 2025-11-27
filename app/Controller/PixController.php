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

namespace App\Controller;

use App\Helper\EmailHelper;
use App\Helper\JsonResponse;
use App\Model\PixKey;
use App\Request\PixStoreRequest;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Swagger\Annotation as SA;

class PixController
{
    #[SA\Post(
        path: '/api/v1/accounts/{accountId}/pix',
        summary: 'Cadastrar chave PIX',
        security: [['bearer' => []]],
        tags: ['PIX']
    )]
    #[SA\PathParameter(
        name: 'accountId',
        description: 'ID da conta',
        required: true,
        schema: new SA\Schema(type: 'string')
    )]
    #[SA\RequestBody(
        description: 'Dados da chave PIX',
        required: true,
        content: new SA\JsonContent(
            required: ['type', 'key'],
            properties: [
                new SA\Property(
                    property: 'type',
                    description: 'Tipo da chave PIX',
                    type: 'string',
                    enum: ['cpf', 'cnpj', 'email', 'phone', 'random']
                ),
                new SA\Property(
                    property: 'key',
                    description: 'Valor da chave PIX',
                    type: 'string',
                    example: 'teste@example.com'
                ),
            ]
        )
    )]
    #[SA\Response(
        response: 201,
        description: 'Chave PIX cadastrada com sucesso',
        content: new SA\JsonContent(
            properties: [
                new SA\Property(property: 'success', type: 'boolean', example: true),
                new SA\Property(property: 'message', type: 'string', example: 'Chave PIX criada com sucesso'),
                new SA\Property(
                    property: 'data',
                    properties: [
                        new SA\Property(property: 'id', type: 'integer', example: 1),
                        new SA\Property(property: 'account_id', type: 'string', example: '1'),
                        new SA\Property(property: 'key_type', type: 'string', example: 'email'),
                        new SA\Property(property: 'key_value', type: 'string', example: 'teste@example.com'),
                        new SA\Property(property: 'status', type: 'string', example: 'active'),
                        new SA\Property(property: 'created_at', type: 'string', example: '2024-01-15 10:30:00'),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[SA\Response(
        response: 422,
        description: 'Erro de validação',
        content: new SA\JsonContent(
            properties: [
                new SA\Property(property: 'success', type: 'boolean', example: false),
                new SA\Property(property: 'message', type: 'string', example: 'The given data was invalid'),
                new SA\Property(
                    property: 'errors',
                    properties: [
                        new SA\Property(property: 'type', type: 'array', items: new SA\Items(type: 'string')),
                        new SA\Property(property: 'key', type: 'array', items: new SA\Items(type: 'string')),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    public function store(PixStoreRequest $request, ResponseInterface $response, string $accountId)
    {
        $data = $request->all();

        $result = PixKey::create([
            'account_id' => $accountId,
            'key_type' => $data['type'],
            'key_value' => $data['key'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Envia email de confirmação de criação da chave PIX
        EmailHelper::sendPixKeyCreatedNotification(
            $accountId,
            $data['type'],
            $data['key']
        );

        return JsonResponse::success(
            $response,
            $result->toArray(),
            201,
            'Chave PIX criada com sucesso'
        );
    }
}
