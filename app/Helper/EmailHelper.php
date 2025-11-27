<?php

declare(strict_types=1);

namespace App\Helper;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailHelper
{
    private const DEFAULT_RECIPIENT = 'cliente@example.com';
    private const SENDER_EMAIL = 'noreply@tecnofit.com.br';
    private const SENDER_NAME = 'Tecnofit Sistema';

    private const TRANSACTION_TYPES = [
        'withdraw' => 'Saque',
        'deposit' => 'Depósito',
        'debit' => 'Débito',
        'credit' => 'Crédito',
    ];

    /**
     * Configura e retorna uma instância do PHPMailer
     */
    private static function getMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = getenv('MAIL_HOST') ?: 'mailhog';
            $mail->Port = (int) (getenv('MAIL_PORT') ?: 1025);
            $mail->SMTPAuth = false;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom(self::SENDER_EMAIL, self::SENDER_NAME);
        } catch (\Exception $e) {
            self::logError("Erro ao configurar mailer: {$mail->ErrorInfo}");
        }

        return $mail;
    }

    /**
     * Envia um email genérico
     */
    private static function sendEmail(
        string $recipientEmail,
        string $subject,
        string $htmlBody,
        string $textBody
    ): bool {
        try {
            $mail = self::getMailer();
            $mail->addAddress($recipientEmail);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody;

            $mail->send();
            self::logSuccess("Email enviado para {$recipientEmail}");
            return true;
        } catch (Exception $e) {
            self::logError("Erro ao enviar email: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Formata valor monetário
     */
    private static function formatCurrency(float $value): string
    {
        return sprintf('R$ %.2f', $value);
    }

    /**
     * Obtém o label da transação
     */
    private static function getTransactionLabel(string $type): string
    {
        return self::TRANSACTION_TYPES[$type] ?? 'Transação';
    }

    /**
     * Log de sucesso
     */
    private static function logSuccess(string $message): void
    {
        echo "[EMAIL] {$message}" . PHP_EOL;
    }

    /**
     * Log de erro
     */
    private static function logError(string $message): void
    {
        echo "[EMAIL ERROR] {$message}" . PHP_EOL;
    }

    /**
     * Envia um email de notificação de saque agendado
     */
    public static function sendScheduledWithdrawNotification(
        string $accountId,
        float $amount,
        string $scheduledFor,
        string $recipientEmail = self::DEFAULT_RECIPIENT
    ): bool {
        $subject = 'Saque Agendado Processado com Sucesso';

        $htmlBody = sprintf(
            '<h2>Saque Processado</h2>
            <p>Seu saque agendado foi processado com sucesso!</p>
            <ul>
                <li><strong>Conta:</strong> %s</li>
                <li><strong>Valor:</strong> %s</li>
                <li><strong>Data Agendada:</strong> %s</li>
            </ul>
            <p>O valor já foi debitado da sua conta.</p>',
            $accountId,
            self::formatCurrency($amount),
            $scheduledFor
        );

        $textBody = sprintf(
            "Saque Processado\n\nConta: %s\nValor: %s\nData Agendada: %s\n\nO valor já foi debitado da sua conta.",
            $accountId,
            self::formatCurrency($amount),
            $scheduledFor
        );

        return self::sendEmail($recipientEmail, $subject, $htmlBody, $textBody);
    }

    /**
     * Envia um email de erro no processamento de saque agendado
     */
    public static function sendScheduledWithdrawError(
        string $accountId,
        float $amount,
        string $errorReason,
        string $recipientEmail = self::DEFAULT_RECIPIENT
    ): bool {
        $subject = 'Erro ao Processar Saque Agendado';

        $htmlBody = sprintf(
            '<h2>Erro no Processamento do Saque</h2>
            <p>Infelizmente, não foi possível processar seu saque agendado.</p>
            <ul>
                <li><strong>Conta:</strong> %s</li>
                <li><strong>Valor:</strong> %s</li>
                <li><strong>Motivo:</strong> %s</li>
            </ul>
            <p>Por favor, entre em contato com o suporte ou tente novamente mais tarde.</p>',
            $accountId,
            self::formatCurrency($amount),
            $errorReason
        );

        $textBody = sprintf(
            "Erro no Processamento do Saque\n\nConta: %s\nValor: %s\nMotivo: %s\n\nPor favor, entre em contato com o suporte.",
            $accountId,
            self::formatCurrency($amount),
            $errorReason
        );

        return self::sendEmail($recipientEmail, $subject, $htmlBody, $textBody);
    }

    /**
     * Envia um email de notificação de transação bem-sucedida
     */
    public static function sendTransactionNotification(
        string $accountId,
        string $transactionType,
        float $amount,
        float $balanceBefore,
        float $balanceAfter,
        string $description,
        string $recipientEmail = self::DEFAULT_RECIPIENT
    ): bool {
        $typeLabel = self::getTransactionLabel($transactionType);
        $subject = "Transação Realizada com Sucesso - {$typeLabel}";

        $htmlBody = sprintf(
            '<h2>%s Realizado com Sucesso</h2>
            <p>Uma transação foi realizada na sua conta.</p>
            <ul>
                <li><strong>Conta:</strong> %s</li>
                <li><strong>Tipo:</strong> %s</li>
                <li><strong>Valor:</strong> %s</li>
                <li><strong>Descrição:</strong> %s</li>
                <li><strong>Saldo Anterior:</strong> %s</li>
                <li><strong>Saldo Atual:</strong> %s</li>
            </ul>
            <p>Se você não reconhece esta transação, entre em contato com o suporte imediatamente.</p>',
            $typeLabel,
            $accountId,
            $typeLabel,
            self::formatCurrency($amount),
            $description,
            self::formatCurrency($balanceBefore),
            self::formatCurrency($balanceAfter)
        );

        $textBody = sprintf(
            "%s Realizado com Sucesso\n\nConta: %s\nTipo: %s\nValor: %s\nDescrição: %s\nSaldo Anterior: %s\nSaldo Atual: %s\n\nSe você não reconhece esta transação, entre em contato com o suporte.",
            $typeLabel,
            $accountId,
            $typeLabel,
            self::formatCurrency($amount),
            $description,
            self::formatCurrency($balanceBefore),
            self::formatCurrency($balanceAfter)
        );

        return self::sendEmail($recipientEmail, $subject, $htmlBody, $textBody);
    }

    /**
     * Envia um email de confirmação de criação de chave PIX
     */
    public static function sendPixKeyCreatedNotification(
        string $accountId,
        string $keyType,
        string $keyValue,
        string $recipientEmail = self::DEFAULT_RECIPIENT
    ): bool {
        $keyTypeLabels = [
            'cpf' => 'CPF',
            'cnpj' => 'CNPJ',
            'email' => 'E-mail',
            'phone' => 'Telefone',
            'random' => 'Chave Aleatória',
        ];

        $keyTypeLabel = $keyTypeLabels[$keyType] ?? 'Chave PIX';
        $subject = 'Chave PIX Criada com Sucesso';

        $htmlBody = sprintf(
            '<h2>Chave PIX Cadastrada</h2>
            <p>Sua chave PIX foi criada com sucesso!</p>
            <ul>
                <li><strong>Conta:</strong> %s</li>
                <li><strong>Tipo de Chave:</strong> %s</li>
                <li><strong>Chave:</strong> %s</li>
                <li><strong>Status:</strong> Ativa</li>
            </ul>
            <p>Agora você pode receber transferências usando esta chave PIX.</p>
            <p><em>Se você não reconhece esta operação, entre em contato com o suporte imediatamente.</em></p>',
            $accountId,
            $keyTypeLabel,
            $keyValue
        );

        $textBody = sprintf(
            "Chave PIX Cadastrada\n\nSua chave PIX foi criada com sucesso!\n\nConta: %s\nTipo de Chave: %s\nChave: %s\nStatus: Ativa\n\nAgora você pode receber transferências usando esta chave PIX.\n\nSe você não reconhece esta operação, entre em contato com o suporte imediatamente.",
            $accountId,
            $keyTypeLabel,
            $keyValue
        );

        return self::sendEmail($recipientEmail, $subject, $htmlBody, $textBody);
    }
}
