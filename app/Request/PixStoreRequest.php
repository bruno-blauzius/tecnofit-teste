<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Contract\ValidatorInterface;
use Hyperf\Validation\ValidationException;

class PixStoreRequest extends FormRequest
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
            'type'  => 'required|string|in:email,cpf,cnpj,phone,random',
            'key'   => 'required|string',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'type'  => 'tipo da chave PIX',
            'key'   => 'chave PIX',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'O campo :attribute é obrigatório.',
            'type.in'       => 'O :attribute deve ser um dos seguintes valores: email, cpf, cnpj, phone ou random.',
            'key.required'  => 'A :attribute é obrigatória.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @throws ValidationException
     */
    protected function failedValidation(ValidatorInterface $validator)
    {
        /** @var \Hyperf\HttpServer\Contract\ResponseInterface $response */
        $response = $this->container->get(ResponseInterface::class);
        $payload = [
            'message' => 'Os dados enviados são inválidos.',
            'errors'  => $validator->errors()->messages(),
        ];

        throw new ValidationException($validator, $response->withBody(new SwooleStream(json_encode($payload)))->withStatus(422)->withHeader('Content-Type', 'application/json'));
    }
}
