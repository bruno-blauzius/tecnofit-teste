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

class AccountStoreRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'balance' => 'required|numeric|min:0',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'balance' => 'saldo',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O campo :attribute é obrigatório.',
            'name.string' => 'O campo :attribute deve ser texto.',
            'name.max' => 'O campo :attribute deve ter no máximo :max caracteres.',
            'balance.required' => 'O campo :attribute é obrigatório.',
            'balance.numeric' => 'O campo :attribute deve ser numérico.',
            'balance.min' => 'O campo :attribute deve ser maior ou igual a :min.',
        ];
    }

    protected function failedValidation(ValidatorInterface $validator)
    {
        /** @var HttpResponse $response */
        $response = $this->container->get(HttpResponse::class);
        $payload = [
            'message' => 'Os dados enviados são inválidos.',
            'errors' => $validator->errors()->messages(),
        ];

        throw new ValidationException(
            $validator,
            $response->withStatus(422)
                ->withHeader('Content-Type', 'application/json')
                ->withBody(new SwooleStream(json_encode($payload, JSON_UNESCAPED_UNICODE)))
        );
    }
}
