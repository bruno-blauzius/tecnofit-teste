<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;
use Hyperf\Contract\ValidatorInterface;
use Hyperf\HttpMessage\Server\Response as HttpResponse;
use Hyperf\Validation\ValidationException;
use Hyperf\HttpMessage\Stream\SwooleStream;


class RegisterRequest extends FormRequest
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
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'confirm_password' => 'required|string|min:6|same:password',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'  => 'Nome',
            'email' => 'E-mail',
            'password' => 'Senha',
            'confirm_password' => 'Confirmação de Senha',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O campo :attribute é obrigatório.',
            'name.string' => 'O campo :attribute deve ser uma string.',
            'name.max' => 'O campo :attribute não pode ter mais de :max caracteres.',
            'email.required' => 'O campo :attribute é obrigatório.',
            'email.email' => 'O campo :attribute deve ser um e-mail válido.',
            'email.max' => 'O campo :attribute não pode ter mais de :max caracteres.',
            'password.required' => 'O campo :attribute é obrigatório.',
            'password.string' => 'O campo :attribute deve ser uma string.',
            'password.min' => 'O campo :attribute deve ter no mínimo :min caracteres.',
            'confirm_password.required' => 'O campo :attribute é obrigatório.',
            'confirm_password.string' => 'O campo :attribute deve ser uma string.',
            'confirm_password.min' => 'O campo :attribute deve ter no mínimo :min caracteres.',
            'confirm_password.same' => 'A confirmação de senha não confere com a senha.',
        ];
    }

    protected function failedValidation(ValidatorInterface $validator)
    {
        /** @var HttpResponse $response */
        $response = $this->container->get(HttpResponse::class);
        $payload = [
            'message' => 'Os dados enviados são inválidos.',
            'errors'  => $validator->errors()->messages(),
        ];

        throw new ValidationException(
            $validator,
            $response->withStatus(422)
                ->withHeader('Content-Type', 'application/json')
                ->withBody(new SwooleStream(json_encode($payload, JSON_UNESCAPED_UNICODE)))
        );
    }
}
