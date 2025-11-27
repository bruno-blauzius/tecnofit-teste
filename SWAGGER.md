# Documentação da API - Tecnofit

## Swagger/OpenAPI

A documentação interativa da API está disponível através do Swagger UI.

### Como acessar

1. Certifique-se de que o servidor está rodando:
```bash
docker compose up -d
```

2. Gere a documentação Swagger (necessário apenas uma vez ou após alterações nos controllers):
```bash
docker compose exec application php generate-swagger.php
```

3. Acesse a documentação Swagger em seu navegador:
```
http://localhost:9501/swagger
```

Ou acesse diretamente o JSON da documentação:
```
http://localhost:9501/swagger.json
```

### Endpoints Documentados

#### Autenticação
- **POST** `/api/v1/public/auth` - Login e obtenção de token JWT

#### Registro
- **POST** `/api/v1/public/register` - Registro de novo usuário

#### Contas
- **GET** `/api/v1/accounts` - Listar todas as contas
- **POST** `/api/v1/accounts` - Criar nova conta
- **PUT** `/api/v1/accounts/{accountId}` - Atualizar saldo da conta (protegido)

#### Saques
- **POST** `/api/v1/accounts/{accountId}/balance/withdraw` - Realizar saque (imediato ou agendado)

### Autenticação com JWT

Para endpoints protegidos, você precisa:

1. Fazer login através do endpoint `/api/v1/public/auth`
2. Copiar o token JWT retornado
3. No Swagger UI, clicar no botão "Authorize" (cadeado)
4. Inserir o token no formato: `Bearer {seu-token-aqui}`
5. Clicar em "Authorize"

Agora você pode testar os endpoints protegidos diretamente pelo Swagger UI.

### Exemplos de Requisições

#### 1. Registrar Usuário
```json
POST /api/v1/public/register
{
  "name": "João Silva",
  "email": "joao@example.com",
  "password": "senha123",
  "confirm_password": "senha123"
}
```

#### 2. Fazer Login
```json
POST /api/v1/public/auth
{
  "email": "joao@example.com",
  "password": "senha123"
}
```

#### 3. Criar Conta
```json
POST /api/v1/public/accounts
{
  "name": "Conta Principal",
  "balance": 100.00
}
```

#### 4. Atualizar Saldo da Conta
```json
PUT /api/v1/accounts/{accountId}
{
  "balance": 1500.50
}
```

#### 5. Realizar Saque
```json
POST /api/v1/public/accounts/{accountId}/balance/withdraw
{
  "amount": 50.00,
  "method": "pix",
  "pix": {
    "type": "cpf",
    "key": "12345678900"
  }
}
```

#### 6. Agendar Saque
```json
POST /api/v1/public/accounts/{accountId}/balance/withdraw
{
  "amount": 50.00,
  "method": "pix",
  "pix": {
    "type": "email",
    "key": "usuario@example.com"
  },
  "schedule": "2024-12-31 14:30"
}
```

### Estrutura de Respostas

#### Resposta de Sucesso
```json
{
  "success": true,
  "message": "Operação realizada com sucesso",
  "data": {
    // dados retornados
  }
}
```

#### Resposta de Erro
```json
{
  "success": false,
  "message": "Mensagem de erro",
  "errors": {
    "campo": ["mensagem de erro do campo"]
  }
}
```

### Tipos de Chave PIX Suportados
- `cpf` - CPF (11 dígitos)
- `cnpj` - CNPJ (14 dígitos)
- `email` - E-mail
- `phone` - Telefone
- `random` - Chave aleatória

### Códigos HTTP

- `200` - Sucesso
- `201` - Criado com sucesso
- `401` - Não autenticado
- `404` - Não encontrado
- `422` - Erro de validação
- `500` - Erro interno do servidor

### Regenerar Documentação

Se você fizer alterações nos controllers, regenere a documentação manualmente:
```bash
docker compose exec application php generate-swagger.php
```

A documentação será atualizada automaticamente em `http://localhost:9501/swagger`### Configuração

A configuração do Swagger está localizada em:
```
config/autoload/swagger.php
```

Você pode personalizar:
- Porta do servidor
- Diretório de saída do JSON
- Caminhos escaneados para anotações
- Habilitar/desabilitar a geração automática
