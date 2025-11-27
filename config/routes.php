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
use App\Controller\AccountController;
use App\Controller\AuthController;
use App\Controller\IndexController;
use App\Controller\PixController;
use App\Controller\RegisterController;
use App\Controller\SwaggerUIController;
use App\Middleware\Auth\AuthMiddleware;
use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'POST', 'HEAD'], '/', IndexController::class . '@index');

Router::get('/favicon.ico', function () {
    return '';
});

// Swagger Documentation
Router::get('/swagger', [SwaggerUIController::class, 'ui']);
Router::get('/swagger.json', [SwaggerUIController::class, 'json']);

// Public routes (without authentication for testing)
Router::addGroup('/api/v1/public', function () {
    Router::get('/accounts', [AccountController::class, 'index']);
    Router::post('/accounts', [AccountController::class, 'store']);
    Router::post('/accounts/{accountId}/balance/withdraw', [AccountController::class, 'withdraw']);
    Router::post('/accounts/{accountId}/pix', [PixController::class, 'store']);
    Router::post('/register', [RegisterController::class, 'store']);
    Router::post('/auth', [AuthController::class, 'authenticate']);
});

// Protected routes (require JWT)

Router::addGroup('/api/v1', function () {
    /*
     * Accounts Routes
     */
    Router::get('/accounts', [AccountController::class, 'index']);
    Router::post('/accounts', [AccountController::class, 'store']);
    Router::put('/accounts/{accountId}', [AccountController::class, 'update']);
    Router::post('/accounts/{accountId}/balance/withdraw', [AccountController::class, 'withdraw']);

    /*
     * Pix Routes
     */
    Router::post('/accounts/{accountId}/pix', [PixController::class, 'store']);

    /*
     * Register route
     */
    Router::post('/register', [RegisterController::class, 'store']);
}, ['middleware' => [AuthMiddleware::class]]);
