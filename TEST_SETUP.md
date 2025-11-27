# Configuração de Testes

## Pré-requisitos

- Docker e Docker Compose instalados
- Containers rodando: `docker compose up -d`

## Setup do Banco de Dados de Testes

O banco de testes utiliza um container MySQL separado (`db-test`) com armazenamento em memória (tmpfs) para melhor performance e isolamento.

### 1. Criar o Schema do Banco de Testes

Execute o seguinte comando para criar as tabelas necessárias:

```bash
docker compose exec db-test mysql -utest -ptest hyperf_test -e "
CREATE TABLE IF NOT EXISTS account (
    id CHAR(36) PRIMARY KEY,
    balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS account_withdraw (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (account_id) REFERENCES account(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS account_withdraw_pix (
    id CHAR(36) PRIMARY KEY,
    account_withdraw_id CHAR(36) NOT NULL,
    key_value VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (account_withdraw_id) REFERENCES account_withdraw(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
"
```

### 2. Verificar as Tabelas Criadas

```bash
docker compose exec db-test mysql -utest -ptest hyperf_test -e "SHOW TABLES;"
```

Você deve ver:
```
+------------------------+
| Tables_in_hyperf_test  |
+------------------------+
| account                |
| account_withdraw       |
| account_withdraw_pix   |
+------------------------+
```

## Executar os Testes

### Executar todos os testes:

```bash
docker compose exec application composer test
```

### Executar testes específicos:

```bash
# Testes do Controller
docker compose exec hyperf-skeleton ./vendor/bin/phpunit test/Cases/Controller/AccountControllerTest.php

# Testes do UseCase
docker compose exec hyperf-skeleton ./vendor/bin/phpunit test/Cases/UseCase/Account/WithdrawUseCaseTest.php
```

### Executar com cobertura de código:

```bash
docker compose exec application ./vendor/bin/phpunit --coverage-html coverage/
```

## Detalhes da Configuração

### Container de Teste (db-test)
- **Host:** db-test
- **Porta:** 3307 (externa), 3306 (interna)
- **Usuário:** test
- **Senha:** test
- **Database:** hyperf_test
- **Armazenamento:** tmpfs (em memória - dados são perdidos ao reiniciar)

### Container de Desenvolvimento (db)
- **Host:** db
- **Porta:** 3306
- **Usuário:** root
- **Senha:** root
- **Database:** hyperf
- **Armazenamento:** volume persistente

### Configuração PHPUnit

O arquivo `phpunit.xml.dist` já está configurado com as variáveis de ambiente corretas:

```xml
<env name="DB_HOST" value="db-test"/>
<env name="DB_DATABASE" value="hyperf_test"/>
<env name="DB_USERNAME" value="test"/>
<env name="DB_PASSWORD" value="test"/>
```

## Limpeza do Banco de Testes

Se precisar resetar o banco de testes, você pode:

### Opção 1: Recriar as tabelas
```bash
docker compose exec db-test mysql -utest -ptest hyperf_test -e "DROP TABLE IF EXISTS account_withdraw_pix, account_withdraw, account; SOURCE /caminho/para/schema.sql"
```

### Opção 2: Reiniciar o container (perde todos os dados - tmpfs)
```bash
docker compose restart db-test
# Executar novamente o script de criação das tabelas
```

### Opção 3: Limpar os dados manualmente
```bash
docker compose exec db-test mysql -utest -ptest hyperf_test -e "
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE account_withdraw_pix;
TRUNCATE TABLE account_withdraw;
TRUNCATE TABLE account;
SET FOREIGN_KEY_CHECKS = 1;
"
```

## Troubleshooting

### Erro: Table doesn't exist
Execute o script de criação das tabelas novamente.

### Erro: Connection refused
Verifique se o container db-test está rodando:
```bash
docker compose ps db-test
```

### Testes lentos
O banco de testes usa tmpfs (RAM) para melhor performance. Se ainda estiver lento:
- Aumente a memória disponível para Docker
- Verifique se o tmpfs está configurado corretamente no `docker-compose.yml`

## CI/CD

Para integração contínua, adicione estes passos no seu pipeline:

```yaml
- name: Setup Test Database
  run: |
    docker compose up -d db-test
    sleep 5
    docker compose exec -T db-test mysql -utest -ptest hyperf_test < test/schema.sql

- name: Run Tests
  run: docker compose exec -T application composer test
```

## Estrutura de Testes

```
test/
├── bootstrap.php                                 # Bootstrap dos testes
├── HttpTestCase.php                              # Classe base para testes HTTP
└── Cases/
    ├── Controller/
    │   └── AccountControllerTest.php             # Testes dos endpoints
    └── UseCase/
        └── Account/
            └── WithdrawUseCaseTest.php           # Testes de lógica de negócio
```

## Notas Importantes

1. **Isolamento**: Cada teste deve ser independente. Use `setUp()` e `tearDown()` quando necessário.
2. **Dados de Teste**: Os dados são voláteis (tmpfs) - perfeito para testes.
3. **Performance**: tmpfs oferece velocidade similar ao SQLite, mas com compatibilidade total do MySQL.
4. **Foreign Keys**: As tabelas usam CASCADE DELETE para manter integridade referencial.
5. **Charset**: Todas as tabelas usam `utf8mb4_unicode_ci` para suporte completo a Unicode.
