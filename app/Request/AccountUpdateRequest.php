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

namespace App\Request;

use Hyperf\Contract\ValidatorInterface;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Hyperf\Validation\Request\FormRequest;
use Hyperf\Validation\ValidationException;

class AccountUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'balance' => 'required|numeric|min:0',
        ];
    }

    public function attributes(): array
    {
        return [
            'balance' => 'saldo',
        ];
    }

    public function messages(): array
    {
        return [
            'balance.required' => 'O campo :attribute é obrigatório.',
            'balance.numeric' => 'O campo :attribute deve ser numérico.',
            'balance.min' => 'O campo :attribute deve ser maior ou igual a :min.',
        ];
    }

    protected function failedValidation(ValidatorInterface $validator): void
    {
        $errors = $validator->errors()->toArray();

        throw new ValidationException($validator, $this->buildFailedValidationResponse($errors));
    }

    private function buildFailedValidationResponse(array $errors): HttpResponse
    {
        $response = $this->container->get(HttpResponse::class);

        return $response
            ->withStatus(422)
            ->withAddedHeader('content-type', 'application/json')
            ->withBody(new SwooleStream(json_encode([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ])));
    }
}
