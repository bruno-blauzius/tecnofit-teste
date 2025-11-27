<?php

declare(strict_types=1);

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateAccountWithdrawTable extends Migration
{
    public function up(): void
    {
        Schema::create('account_withdraw', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('account_id');
            $table
                ->foreign('account_id')
                ->references('id')
                ->on('account')
                ->onDelete('cascade');

            $table->string('method', 50);
            $table->decimal('amount', 10, 2);

            $table->boolean('scheduled')->default(false);
            $table->dateTime('scheduled_for')->nullable();

            $table->boolean('done')->default(false);

            $table->boolean('error')->default(false);
            $table->text('error_reason')->nullable();

            $table->timestamps(); // created_at, updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_withdraw');
    }
}
