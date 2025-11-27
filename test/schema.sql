-- Schema do Banco de Dados de Testes
-- Database: hyperf_test

-- Tabela de Contas
CREATE TABLE IF NOT EXISTS account (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Saques
CREATE TABLE IF NOT EXISTS account_withdraw (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    method VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    scheduled BOOLEAN NOT NULL DEFAULT FALSE,
    scheduled_for TIMESTAMP NULL DEFAULT NULL,
    done BOOLEAN NOT NULL DEFAULT FALSE,
    error BOOLEAN NOT NULL DEFAULT FALSE,
    error_reason TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (account_id) REFERENCES account(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Dados PIX dos Saques
CREATE TABLE IF NOT EXISTS account_withdraw_pix (
    id CHAR(36) PRIMARY KEY,
    account_withdraw_id CHAR(36) NOT NULL,
    type VARCHAR(50) NOT NULL,
    key_value VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (account_withdraw_id) REFERENCES account_withdraw(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Histórico de Transações
CREATE TABLE IF NOT EXISTS account_transaction_history (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    type ENUM('withdraw', 'deposit', 'debit', 'credit') NOT NULL COMMENT 'Tipo de transação',
    amount DECIMAL(10,2) NOT NULL COMMENT 'Valor da transação',
    balance_before DECIMAL(10,2) NOT NULL COMMENT 'Saldo antes da transação',
    balance_after DECIMAL(10,2) NOT NULL COMMENT 'Saldo depois da transação',
    description VARCHAR(255) NULL COMMENT 'Descrição da transação',
    reference_id CHAR(36) NULL COMMENT 'ID de referência (withdraw_id, etc)',
    reference_type VARCHAR(50) NULL COMMENT 'Tipo de referência',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (account_id) REFERENCES account(id) ON DELETE CASCADE,
    INDEX idx_account_id (account_id),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Usuários
CREATE TABLE IF NOT EXISTS users (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(64) NOT NULL,
    account_id CHAR(36) NULL UNIQUE,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (account_id) REFERENCES account(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
