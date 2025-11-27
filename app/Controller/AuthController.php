<?php

declare(strict_types=1);

namespace App\Controller;

use App\Helper\JwtHelper;
use App\Helper\JsonResponse;
use App\Model\User;
use App\Request\AuthRequest;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Context\ApplicationContext;
use OpenApi\Attributes as OA;

class AuthController
{
    #[OA\Post(
        path: '/api/v1/public/auth',
        summary: 'Autenticação de usuário',
        description: 'Realiza a autenticação do usuário e retorna um token JWT',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        tags: ['Autenticação'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login realizado com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Login realizado com sucesso!'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'),
                                new OA\Property(property: 'type', type: 'string', example: 'Bearer'),
                                new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
                                new OA\Property(property: 'refresh_token', type: 'string', nullable: true, example: null),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Credenciais inválidas',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Erro de validação',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function authenticate(AuthRequest $request, ResponseInterface $response)
    {
        $email = $request->input('email');
        $password = $request->input('password');

        // Busca o usuário pelo email
        $user = User::where('email', $email)->first();

        // Verifica se o usuário existe e se a senha está correta
        if (!$user || !$user->verifyPassword($password)) {
            return JsonResponse::error($response, 'Credenciais inválidas', 401);
        }

        // Gera o token JWT
        $token = JwtHelper::generate($user->id, $user->email);

        $container = ApplicationContext::getContainer();
        $config = $container->get(\Hyperf\Contract\ConfigInterface::class);
        $expiresIn = $config->get('jwt.expiration', 3600);

        return JsonResponse::success($response, [
            'token' => $token,
            'type' => 'Bearer',
            'expires_in' => $expiresIn,
            'refresh_token' => null,
        ], 200, 'Login realizado com sucesso!');
    }
}
