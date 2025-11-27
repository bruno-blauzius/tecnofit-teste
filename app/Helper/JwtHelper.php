<?php

declare(strict_types=1);

namespace App\Helper;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Hyperf\Context\ApplicationContext;

class JwtHelper
{
    /**
     * Gera um token JWT
     */
    public static function generate(string $userId, string $email): string
    {
        $container = ApplicationContext::getContainer();
        $config = $container->get(\Hyperf\Contract\ConfigInterface::class);

        $secret = $config->get('jwt.secret', 'your-secret-key-change-this');
        $expirationTime = $config->get('jwt.expiration', 3600);

        $payload = [
            'iss' => 'tecnofit-api',
            'aud' => 'tecnofit-app',
            'iat' => time(),
            'exp' => time() + $expirationTime,
            'sub' => $userId,
            'email' => $email,
        ];

        return JWT::encode($payload, $secret, 'HS256');
    }

    /**
     * Valida e decodifica um token JWT
     */
    public static function decode(string $token): object
    {
        $container = ApplicationContext::getContainer();
        $config = $container->get(\Hyperf\Contract\ConfigInterface::class);

        $secret = $config->get('jwt.secret', 'your-secret-key-change-this');

        return JWT::decode($token, new Key($secret, 'HS256'));
    }

    /**
     * Valida se o token é válido
     */
    public static function validate(string $token): bool
    {
        try {
            self::decode($token);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
