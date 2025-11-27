<?php

declare(strict_types=1);

namespace App\Controller;

use App\Request\RegisterRequest as RegisterFormRequest;
use App\UseCase\User\RegisterRequest;
use App\UseCase\User\RegisterUseCase;
use Hyperf\HttpServer\Contract\ResponseInterface;
use App\Helper\JsonResponse;
use OpenApi\Attributes as OA;

class RegisterController extends AbstractController
{

    public function __construct(
        protected RegisterUseCase $registerUseCase,
    ) {}

    #[OA\Post(
        path: '/api/v1/public/register',
        summary: 'Registro de novo usuário',
        description: 'Cria um novo usuário e uma conta associada com saldo inicial de R$ 0,00',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'confirm_password'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'João Silva'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'joao@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 6, example: 'senha123'),
                    new OA\Property(property: 'confirm_password', type: 'string', format: 'password', example: 'senha123'),
                ]
            )
        ),
        tags: ['Registro'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Usuário registrado com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Usuário registrado com sucesso!'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
                                new OA\Property(property: 'name', type: 'string', example: 'João Silva'),
                                new OA\Property(property: 'email', type: 'string', example: 'joao@example.com'),
                                new OA\Property(property: 'account_id', type: 'string', format: 'uuid', example: '660e8400-e29b-41d4-a716-446655440111'),
                                new OA\Property(
                                    property: 'account',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                        new OA\Property(property: 'balance', type: 'number', format: 'float', example: 0.00),
                                    ],
                                    type: 'object'
                                ),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Erro de validação',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
            new OA\Response(
                response: 500,
                description: 'Erro interno do servidor',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function store(RegisterFormRequest $request, ResponseInterface $response)
    {
        try {
            $validated = $request->validated();

            $dto = new RegisterRequest(
                name: $validated['name'],
                email: $validated['email'],
                password: $validated['password'],
            );

            $result = $this->registerUseCase->execute($dto);

            return JsonResponse::success($response, $result, 201, 'Usuário registrado com sucesso!');

        } catch (\InvalidArgumentException $e) {
            return JsonResponse::error($response, $e->getMessage(), 422);
        } catch (\Exception $e) {
            return JsonResponse::error($response, $e->getMessage(), 500);
        }
    }
}
