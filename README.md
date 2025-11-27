# Tecnofit Teste - API de Contas e Saques

API desenvolvida com Hyperf Framework para gerenciamento de contas bancárias e operações de saque.

## Requisitos

- Docker
- Docker Compose

### Versões

- **PHP:** 8.3
- **Hyperf Framework:** 3.1.0
- **MySQL:** 8.0
- **Swoole:** 5.0+

## Configuração Inicial

### 1. Clone o repositório

```bash
git clone <repository-url>
cd tecnofit-teste
```

### 2. Configure as variáveis de ambiente

Crie o arquivo `.env` baseado no exemplo:

```bash
cp .env.example .env
```

Configurações mínimas necessárias no `.env`:

```env
# Database
DB_DRIVER=mysql
DB_HOST=tecnofit-database
DB_PORT=3306
DB_DATABASE=hyperf_test
DB_USERNAME=tecnofit_application
DB_PASSWORD=Napoleao1689!

# JWT
JWT_SECRET=a-string-secret-at-least-256-bits-long

# MySQL Root
MYSQL_ROOT_PASSWORD=Napoleao1689!
```

### 3. Inicie os containers

```bash
docker compose up -d --build
```

### 4. Execute as migrations

```bash
# Aplicar migrations no banco de testes
docker compose exec application php migrate-test.php migrate:fresh

# Aplicar migrations no banco principal
docker compose exec application php bin/hyperf.php migrate:fresh
```

### 5. Acesse a aplicação

- **API:** http://localhost:9501
- **Swagger:** http://localhost:9501/swagger
- **Mailhog (Interface de Email):** http://localhost:8025
- **Prometheus (Métricas):** http://localhost:9090
- **Grafana (Dashboards):** http://localhost:3000
  - **Usuário:** `admin`
  - **Senha:** `admin123`

## Mailhog - Servidor de Email para Testes

O projeto utiliza o **Mailhog** para capturar e visualizar emails enviados durante o desenvolvimento e testes.

### Como funciona

- Todos os emails enviados pela aplicação são capturados pelo Mailhog
- Nenhum email real é enviado para endereços externos
- Interface web para visualizar todos os emails capturados

### Acessar Interface

Abra no navegador: **http://localhost:8025**

### Configuração SMTP

O Mailhog está configurado no `docker-compose.yml`:

- **Host SMTP:** mailhog
- **Porta SMTP:** 1025
- **Interface Web:** 8025

### Notificações por Email

A aplicação envia emails automáticos para:

- ✉️ **Transações bem-sucedidas** (depósito, crédito, débito)
- ✉️ **Saques agendados** confirmados
- ✉️ **Saques agendados processados** com sucesso
- ✉️ **Erros no processamento** de saques agendados

Todos os emails enviados podem ser visualizados na interface do Mailhog.

## Executar Testes

### Suite Completa (132 testes)

```bash
# Todos os testes (funcionalidades + PIX + schedule)
docker compose exec application composer test
```

**Cobertura atual:**
- ✅ 93 testes originais (contas, saques, transações, autenticação)
- ✅ 31 testes PIX (validação de chaves CPF, CNPJ, email, phone, random)
- ✅ 9 testes de agendamento funcional (lógica de negócio)
- ⏭️ 5 testes de coroutine (pulados no co-phpunit)

### Testes Específicos

```bash
# Testes por módulo
docker compose exec application ./vendor/bin/phpunit test/Cases/Controller/AccountControllerTest.php
docker compose exec application ./vendor/bin/phpunit test/Cases/UseCase/Account/WithdrawUseCaseTest.php
docker compose exec application ./vendor/bin/phpunit test/Cases/Model/PixKeyTest.php

# Testes de agendamento funcional (lógica de negócio)
docker compose exec application ./vendor/bin/phpunit test/Cases/UseCase/Schedule/ScheduleUseCaseFunctionalTest.php

# Testes de processamento paralelo (fora do co-phpunit)
docker compose exec application php vendor/bin/phpunit test/Cases/UseCase/Schedule/ScheduleUseCaseCoroutineTest.php
```

**Nota:** Testes de coroutine devem ser executados com `php vendor/bin/phpunit` (sem co-phpunit) para testar o processamento paralelo real.

## Principais Endpoints

### Endpoints Públicos (sem autenticação)

#### Autenticação

- `POST /api/v1/public/register` - Registrar novo usuário
- `POST /api/v1/public/auth` - Autenticar e obter token JWT

#### Contas

- `POST /api/v1/accounts` - Criar conta
- `GET /api/v1/accounts` - Listar contas

### Endpoints Protegidos (requer autenticação JWT)

#### Contas

- `PUT /api/v1/accounts/{accountId}` - Atualizar saldo

#### Saques

- `POST /api/v1/accounts/{accountId}/balance/withdraw` - Realizar saque (imediato ou agendado)

> **Nota:** Para acessar endpoints protegidos, envie o token JWT no header: `Authorization: Bearer <token>`

## Funcionalidades

- ✅ Criação e gerenciamento de contas
- ✅ Chaves PIX com validação completa (CPF, CNPJ, email, telefone, aleatória)
- ✅ Saques imediatos com validação de saldo
- ✅ Agendamento de saques
- ✅ Processamento automático de saques agendados (Crontab)
- ✅ Processamento paralelo com Coroutines (até 10 jobs simultâneos)
- ✅ Histórico completo de transações
- ✅ Autenticação JWT
- ✅ Notificações por email (Mailhog)
- ✅ Monitoramento com Prometheus/Grafana

## Crontab (Processamento Automático)

O sistema possui um **container separado dedicado ao Crontab** que executa tarefas agendadas automaticamente.

### Container de Crontab

O serviço `application-crontab` roda independentemente do servidor HTTP principal e é responsável por:

- 🔄 **Processar saques agendados** automaticamente a cada minuto
- ⚡ **Execução paralela** de até 10 saques simultâneos usando Coroutines
- 📧 **Envio de notificações** por email após processamento
- 📊 **Logs estruturados** de todas as operações

### Configuração

A configuração do crontab está em `config/autoload/crontab.php`:

```php
return [
    'enable' => true,
    'crontab' => [
        (new Crontab())
            ->setName('process_scheduled_withdraws')
            ->setRule('*/1 * * * *') // a cada 1 minuto
            ->setCallback([\App\Crontab\ProcessScheduledWithdrawsCrontab::class, 'handle'])
            ->setMemo('Processa saques agendados automaticamente'),
    ],
];
```

### Verificar Status do Crontab

```bash
# Ver status dos containers
docker compose ps

# Ver logs do crontab
docker compose logs crontab --tail 50 --follow

# Ver apenas execuções do crontab
docker compose logs crontab | grep "Crontab task"
```

### Como Funciona

1. **A cada minuto**, o crontab busca saques agendados que atendem aos critérios:
   - Data/hora agendada <= momento atual
   - Status = 'pending'
   - Sem erros anteriores

2. **Processamento paralelo**: Até 10 saques são processados simultaneamente usando `Hyperf\Coroutine\Parallel`

3. **Para cada saque processado**:
   - Valida saldo disponível
   - Deduz valor da conta
   - Registra transação no histórico
   - Atualiza status para 'processed'
   - Envia email de confirmação

4. **Tratamento de erros**:
   - Saques com saldo insuficiente são marcados com erro
   - Logs detalhados de todas as operações
   - Rollback automático em caso de falha

### Arquitetura

```
┌─────────────────────────────────────────┐
│   Container: application-crontab        │
│                                         │
│  ┌──────────────────────────────────┐  │
│  │ CrontabDispatcherProcess         │  │
│  │  (executa a cada minuto)         │  │
│  └──────────┬───────────────────────┘  │
│             │                           │
│             v                           │
│  ┌──────────────────────────────────┐  │
│  │ ProcessScheduledWithdrawsCrontab │  │
│  │  - Busca saques agendados        │  │
│  │  - Chama ScheduleUseCase         │  │
│  └──────────┬───────────────────────┘  │
│             │                           │
│             v                           │
│  ┌──────────────────────────────────┐  │
│  │ ScheduleUseCase                  │  │
│  │  - Processamento paralelo        │  │
│  │  - Até 10 coroutines simultâneas │  │
│  └──────────┬───────────────────────┘  │
│             │                           │
│             v                           │
│  ┌──────────────────────────────────┐  │
│  │ ProcessScheduledWithdrawUseCase  │  │
│  │  - Valida e processa cada saque  │  │
│  │  - Atualiza banco de dados       │  │
│  │  - Envia notificação             │  │
│  └──────────────────────────────────┘  │
└─────────────────────────────────────────┘
```

### Logs de Exemplo

```
[INFO] Crontab task [process_scheduled_withdraws] executed successfully at 2025-11-25 22:47:00.
[INFO] [CRONTAB] Iniciando processamento de saques agendados
[INFO] [CRONTAB] Processamento concluído | processed: 3 | failed: 0
[INFO] [CRONTAB] Saques processados com sucesso: 3
[EMAIL] Email enviado para cliente@example.com
```

## Documentação e Testes
- ✅ Documentação Swagger completa
- ✅ 132 testes automatizados (93 originais + 31 PIX + 9 schedule)

## 💡 Sugestões de Melhorias

### 1. Substituir Cron por Arquitetura Event-Driven

**Situação Atual:**
- Crontab executa verificação a cada minuto (polling)
- Processa saques mesmo quando não há novos registros
- Consumo de recursos desnecessário em períodos ociosos

**Melhoria Proposta:**
Implementar arquitetura **Event-Driven** com filas assíncronas:

```php
// Ao criar um saque agendado, dispara um evento
Event::dispatch(new WithdrawScheduledEvent($withdraw));

// Listener processa o evento e adiciona à fila com delay
class WithdrawScheduledListener
{
    public function handle(WithdrawScheduledEvent $event)
    {
        // Calcula delay até a data agendada
        $delay = $event->withdraw->scheduled_at->diffInSeconds(now());

        // Adiciona job à fila com delay
        ProcessScheduledWithdrawJob::dispatch($event->withdraw)
            ->delay($delay);
    }
}
```

**Vantagens:**
- ✅ **Processamento sob demanda** - executa apenas quando necessário
- ✅ **Eficiência de recursos** - zero polling, zero verificações vazias
- ✅ **Escalabilidade** - adiciona workers conforme necessidade
- ✅ **Precisão temporal** - processa exatamente no momento agendado
- ✅ **Retry automático** - suporte nativo a falhas e reprocessamento
- ✅ **Priorização** - jobs podem ter diferentes prioridades
- ✅ **Monitoramento** - status de cada job individual

**Implementação:**
1. Usar `hyperf/async-queue` (já instalado)
2. Criar `ProcessScheduledWithdrawJob`
3. Disparar evento ao criar saque agendado
4. Listener adiciona job à fila com delay calculado
5. Remover container de crontab

**Componentes necessários:**
- Events/WithdrawScheduledEvent.php
- Listeners/WithdrawScheduledListener.php
- Jobs/ProcessScheduledWithdrawJob.php
- Configuração de filas assíncronas

### 2. Outras Melhorias Sugeridas

- **Cache de consultas frequentes** com Redis
- **Rate limiting** para proteção contra abuso
- **Audit log** completo de todas as operações
- **Webhook callbacks** para notificações em tempo real
- **Idempotência** nas operações críticas
- **Circuit breaker** para serviços externos
- **Health checks** e métricas (Prometheus/Grafana)



#  💡 Debitos técnicos

## Processamento de pagamentos


### 🧠 Versão 1
Será um possível problema da forma que está sendo entregue o projeto, nesse processo vejo que a melhor forma de ser feita com mais eficiência e performance é estrutura para essa funcionalidade é o Kafka (MSK) e o EKS Kubernets que são as ferramentas da AWS.

Segue um desenho sugerido para melhoria:

Na opção abaixo pode ser que nós tenhamos algum problema de envio do e-mail e o nosso cliente final pode não receber a informação de pagamento.

![Kubernets pagamento](kubernets-pagamento-v1.drawio.svg)

### 🧠 Versão 2
Na segunda versão existe a melhoria de entrega de e-mail ou notificação para o usuário, essa versão ela deve seguir o padrão da estrutura principal pois o volume deve acompanhar a vazão, mas se a opção for por um baixo custo de para esse envio podemos mudar para SQS e Lambda como infra para os envios de e-mail ou usar uma step-functions se precisarmos enviar um conjunto de chamadas tais como SMS, E-mail, whatsapp ou push notification.

![Kubernets pagamento](kubernets-pagamento-v2.drawio.svg)
