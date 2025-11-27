<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateAccountTransactionHistoryTable extends Migration
{
    public function up(): void
    {
        Schema::create('account_transaction_history', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('account_id');
            $table
                ->foreign('account_id')
                ->references('id')
                ->on('account')
                ->onDelete('cascade');

            $table->enum('type', ['withdraw', 'deposit', 'debit', 'credit'])->comment('Tipo de transação');
            $table->decimal('amount', 10, 2)->comment('Valor da transação');
            $table->decimal('balance_before', 10, 2)->comment('Saldo antes da transação');
            $table->decimal('balance_after', 10, 2)->comment('Saldo depois da transação');
            $table->string('description', 255)->nullable()->comment('Descrição da transação');
            $table->uuid('reference_id')->nullable()->comment('ID de referência (withdraw_id, etc)');
            $table->string('reference_type', 50)->nullable()->comment('Tipo de referência');

            $table->timestamps();

            $table->index('account_id');
            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_transaction_history');
    }
}
