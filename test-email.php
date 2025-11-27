<?php

declare(strict_types=1);

use App\Helper\EmailHelper;

require_once __DIR__ . '/vendor/autoload.php';

echo "=== Teste de Envio de Email para Transações ===" . PHP_EOL . PHP_EOL;

// Teste 1: Email de notificação de saque processado
echo "1. Testando email de notificação de saque processado..." . PHP_EOL;
$result1 = EmailHelper::sendScheduledWithdrawNotification(
    accountId: '550e8400-e29b-41d4-a716-446655440000',
    amount: 150.50,
    scheduledFor: '2025-11-25 14:30:00',
    recipientEmail: 'cliente@example.com'
);

if ($result1) {
    echo "✓ Email de sucesso enviado com sucesso!" . PHP_EOL;
} else {
    echo "✗ Falha ao enviar email de sucesso" . PHP_EOL;
}

echo PHP_EOL;

// Teste 2: Email de erro no processamento
echo "2. Testando email de erro no processamento..." . PHP_EOL;
$result2 = EmailHelper::sendScheduledWithdrawError(
    accountId: '550e8400-e29b-41d4-a716-446655440000',
    amount: 500.00,
    errorReason: 'Saldo insuficiente para realizar o saque',
    recipientEmail: 'cliente@example.com'
);

if ($result2) {
    echo "✓ Email de erro enviado com sucesso!" . PHP_EOL;
} else {
    echo "✗ Falha ao enviar email de erro" . PHP_EOL;
}

echo PHP_EOL;

// Teste 3: Email de transação - Saque
echo "3. Testando email de transação (Saque)..." . PHP_EOL;
$result3 = EmailHelper::sendTransactionNotification(
    accountId: '550e8400-e29b-41d4-a716-446655440000',
    transactionType: 'withdraw',
    amount: 200.00,
    balanceBefore: 1000.00,
    balanceAfter: 800.00,
    description: 'Saque realizado',
    recipientEmail: 'cliente@example.com'
);

if ($result3) {
    echo "✓ Email de transação (Saque) enviado com sucesso!" . PHP_EOL;
} else {
    echo "✗ Falha ao enviar email de transação" . PHP_EOL;
}

echo PHP_EOL;

// Teste 4: Email de transação - Depósito
echo "4. Testando email de transação (Depósito)..." . PHP_EOL;
$result4 = EmailHelper::sendTransactionNotification(
    accountId: '550e8400-e29b-41d4-a716-446655440000',
    transactionType: 'deposit',
    amount: 300.00,
    balanceBefore: 800.00,
    balanceAfter: 1100.00,
    description: 'Saldo inicial da conta',
    recipientEmail: 'cliente@example.com'
);

if ($result4) {
    echo "✓ Email de transação (Depósito) enviado com sucesso!" . PHP_EOL;
} else {
    echo "✗ Falha ao enviar email de transação" . PHP_EOL;
}

echo PHP_EOL;

// Teste 5: Email de transação - Crédito
echo "5. Testando email de transação (Crédito)..." . PHP_EOL;
$result5 = EmailHelper::sendTransactionNotification(
    accountId: '550e8400-e29b-41d4-a716-446655440000',
    transactionType: 'credit',
    amount: 100.00,
    balanceBefore: 1100.00,
    balanceAfter: 1200.00,
    description: 'Atualização de saldo',
    recipientEmail: 'cliente@example.com'
);

if ($result5) {
    echo "✓ Email de transação (Crédito) enviado com sucesso!" . PHP_EOL;
} else {
    echo "✗ Falha ao enviar email de transação" . PHP_EOL;
}

echo PHP_EOL;

// Teste 6: Email de transação - Débito
echo "6. Testando email de transação (Débito)..." . PHP_EOL;
$result6 = EmailHelper::sendTransactionNotification(
    accountId: '550e8400-e29b-41d4-a716-446655440000',
    transactionType: 'debit',
    amount: 50.00,
    balanceBefore: 1200.00,
    balanceAfter: 1150.00,
    description: 'Atualização de saldo',
    recipientEmail: 'cliente@example.com'
);

if ($result6) {
    echo "✓ Email de transação (Débito) enviado com sucesso!" . PHP_EOL;
} else {
    echo "✗ Falha ao enviar email de transação" . PHP_EOL;
}

echo PHP_EOL;
echo "=== Teste Concluído ===" . PHP_EOL;
echo "Acesse http://localhost:8025 para visualizar os 6 emails no Mailhog" . PHP_EOL;
