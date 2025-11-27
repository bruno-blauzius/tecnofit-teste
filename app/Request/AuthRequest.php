<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Contract\ValidatorInterface;
use Hyperf\Validation\ValidationException;

class AuthRequest extends FormRequest
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
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ];
    }


    public function attributes(): array
    {
        return [
            'email'    => 'e-mail',
            'password' => 'senha',
        ];
    }


    public function messages(): array
    {
        return [
            'email.required'    => 'O campo :attribute é obrigatório.',
            'email.email'       => 'O campo :attribute deve ser um e-mail válido.',
            'password.required' => 'O campo :attribute é obrigatório.',
            'password.string'   => 'O campo :attribute deve ser uma string.',
            'password.min'      => 'O campo :attribute deve ter no mínimo :min caracteres.',
        ];
    }


    protected function failedValidation(ValidatorInterface $validator)
    {
        /** @var ResponseInterface $response */
        $response = $this->container->get(ResponseInterface::class);
        $payload = [
            'message' => 'Os dados enviados são inválidos.',
            'errors'  => $validator->errors()->messages(),
        ];

        throw new ValidationException(
            $validator,
            $response->withStatus(422)
                ->json($payload)
        );
    }
}
