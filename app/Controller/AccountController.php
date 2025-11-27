<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Account;
use App\Helper\JsonResponse;
use App\Request\AccountPostWithDrawRequest;
use App\UseCase\Account\CreateAccountRequest;
use App\UseCase\Account\CreateAccountUseCase;
use App\UseCase\Account\WithdrawRequest as WithdrawDto;
use App\UseCase\Account\WithdrawUseCase;
use App\UseCase\Account\Exception\AccountNotFoundException;
use App\UseCase\Account\Exception\InsufficientBalanceException;
use App\UseCase\Account\Exception\InvalidScheduleException;
use App\UseCase\Account\Exception\PixKeyNotFoundException;
use App\UseCase\Account\UpdateBalanceRequest;
use App\UseCase\Account\UpdateBalanceUseCase;
use DateTimeImmutable;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use App\Request\AccountStoreRequest;
use App\Request\AccountUpdateRequest;
use OpenApi\Attributes as OA;


class AccountController extends AbstractController
{
    public function __construct(
        protected CreateAccountUseCase $createAccountUseCase,
        protected WithdrawUseCase $withdrawUseCase,
        protected UpdateBalanceUseCase $updateBalanceUseCase,
    ) {}


    #[OA\Post(
        path: '/api/v1/accounts',
        summary: 'Criar nova conta',
        description: 'Cria uma nova conta com saldo inicial',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['balance'],
                properties: [
                    new OA\Property(property: 'balance', type: 'number', format: 'float', example: 100.00),
                ]
            )
        ),
        tags: ['Contas'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Conta criada com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'balance', type: 'number', format: 'float', example: 100.00),
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
        ]
    )]
    public function store(AccountStoreRequest $request, ResponseInterface $response)
    {
        $validated = $request->validated();

        $dto = new CreateAccountRequest(
            name: (string) $validated['name'],
            balance: (float) $validated['balance'],
        );

        $result = $this->createAccountUseCase->execute($dto);

        return JsonResponse::success($response, $result, 201);
    }


    #[OA\Put(
        path: '/api/v1/{accountId}',
        summary: 'Atualizar saldo da conta',
        description: 'Atualiza o saldo de uma conta existente',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['balance'],
                properties: [
                    new OA\Property(property: 'balance', type: 'number', format: 'float', example: 1500.50, description: 'Novo saldo da conta'),
                ]
            )
        ),
        tags: ['Contas'],
        parameters: [
            new OA\Parameter(
                name: 'accountId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid'),
                description: 'ID da conta'
            ),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Saldo atualizado com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'name', type: 'string', example: 'Conta Principal'),
                                new OA\Property(property: 'balance', type: 'number', format: 'float', example: 1500.50),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Conta não encontrada',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Erro de validação ou saldo inválido',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function update(AccountUpdateRequest $request, ResponseInterface $response, string $accountId)
    {
        $validated = $request->validated();

        $dto = new UpdateBalanceRequest(
            accountId: (string) $accountId,
            balance: (float) $validated['balance'],
        );

        $result = $this->updateBalanceUseCase->execute($dto);

        return JsonResponse::success($response, $result, 201);
    }


    #[OA\Get(
        path: '/api/v1/accounts',
        summary: 'Listar todas as contas',
        description: 'Retorna uma lista de todas as contas cadastradas',
        security: [['bearerAuth' => []]],
        tags: ['Contas'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de contas',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'balance', type: 'number', format: 'float', example: 100.00),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index(RequestInterface $request, ResponseInterface $response)
    {
        $accounts = Account::all()->toArray();
        return JsonResponse::success($response, $accounts);
    }


    #[OA\Post(
        path: '/api/v1/accounts/{accountId}/balance/withdraw',
        summary: 'Realizar saque',
        description: 'Realiza um saque de uma conta. Pode ser saque imediato ou agendado.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount', 'method', 'pix'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 50.00, description: 'Valor do saque'),
                    new OA\Property(property: 'method', type: 'string', enum: ['pix'], example: 'pix', description: 'Método de saque'),
                    new OA\Property(
                        property: 'pix',
                        properties: [
                            new OA\Property(property: 'type', type: 'string', enum: ['cpf', 'cnpj', 'email', 'phone', 'random'], example: 'cpf', description: 'Tipo de chave PIX'),
                            new OA\Property(property: 'key', type: 'string', example: '12345678900', description: 'Chave PIX'),
                        ],
                        type: 'object'
                    ),
                    new OA\Property(property: 'schedule', type: 'string', format: 'date-time', example: '2024-12-31 14:30', description: 'Data e hora do agendamento (opcional, formato: Y-m-d H:i)'),
                ]
            )
        ),
        tags: ['Saques'],
        parameters: [
            new OA\Parameter(
                name: 'accountId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid'),
                description: 'ID da conta'
            ),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Saque realizado ou agendado com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Saque realizado com sucesso.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'withdraw_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'amount', type: 'number', format: 'float', example: 50.00),
                                new OA\Property(property: 'scheduled', type: 'boolean', example: false),
                                new OA\Property(property: 'scheduled_for', type: 'string', format: 'date-time', nullable: true),
                                new OA\Property(property: 'pix_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'new_balance', type: 'number', format: 'float', example: 50.00),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Conta não encontrada',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Erro de validação ou saldo insuficiente',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 500,
                description: 'Erro interno do servidor',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function withdraw(
        string $accountId,
        AccountPostWithDrawRequest $request,
        ResponseInterface $response,
    ) {
        $validated = $request->validated();

        $scheduleAt = null;
        if (!empty($validated['schedule'])) {
            $scheduleAt = DateTimeImmutable::createFromFormat('Y-m-d H:i', $validated['schedule']) ?: null;
        }

        $dto = new WithdrawDto(
            accountId: $accountId,
            amount: (float) $validated['amount'],
            method: $validated['method'],
            pixType: $validated['pix']['type'],
            pixKey: $validated['pix']['key'],
            scheduleAt: $scheduleAt,
        );

        try {

            $result = $this->withdrawUseCase->execute(
                $dto
            );

            $message = $result['scheduled']
                ? 'Saque agendado com sucesso.'
                : 'Saque realizado com sucesso.';

            return JsonResponse::success($response, $result, 201, $message);

        } catch (AccountNotFoundException $e) {
            return JsonResponse::error($response, $e->getMessage(), 404);
        } catch (InsufficientBalanceException|InvalidScheduleException|PixKeyNotFoundException $e) {
            return JsonResponse::error($response, $e->getMessage(), 422);
        } catch (\Throwable $e) {
            var_dump($e->getMessage());
            return JsonResponse::error($response, 'Erro interno ao processar o saque.', 500);
        }
    }

}
