<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Validator;
use Hyperf\Contract\ValidatorInterface;
use Hyperf\Validation\Request\FormRequest;
use Hyperf\Validation\ValidationException;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;

class AccountPostWithDrawRequest extends FormRequest
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
            'method'        => 'required|string|in:PIX',
            'pix'           => 'required|array',
            'pix.type'      => 'required|string|in:email,cpf,cnpj,phone,random',
            'pix.key'       => 'required|string',
            'amount'        => 'required|numeric|min:0.01',
            'schedule'      => ['nullable', 'date_format:Y-m-d H:i'],
        ];
    }

    public function attributes(): array
    {
        return [
            'method'    => 'método',
            'pix'       => 'dados PIX',
            'pix.type'  => 'tipo da chave PIX',
            'pix.key'   => 'chave PIX',
            'amount'    => 'valor',
            'schedule'  => 'agendamento',
        ];
    }

    public function messages(): array
    {
        return [
            'method.required'   => 'O campo :attribute é obrigatório.',
            'method.in'         => 'Por enquanto só suportamos o método PIX.',
            'pix.required'      => 'Os :attribute são obrigatórios.',
            'pix.array'         => 'Os :attribute devem ser um objeto válido.',
            'pix.type.required' => 'O campo :attribute é obrigatório.',
            'pix.type.in'       => 'O :attribute deve ser um dos seguintes valores: email, cpf, cnpj, phone ou random.',
            'pix.key.required'  => 'A :attribute é obrigatória.',
            'amount.required'   => 'O campo :attribute é obrigatório.',
            'amount.numeric'    => 'O campo :attribute deve ser numérico.',
            'amount.min'        => 'O valor mínimo para saque é :min.',
            'schedule.date_format' => 'O campo :attribute deve estar no formato YYYY-MM-DD HH:MM.',
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
