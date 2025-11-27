<?php

declare(strict_types=1);

namespace HyperfTest;

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;

class DatabaseHelper
{
    public static function createTables(): void
    {
        if (Schema::hasTable('account_withdraw_pix')) {
            Schema::dropIfExists('account_withdraw_pix');
        }
        if (Schema::hasTable('account_withdraw')) {
            Schema::dropIfExists('account_withdraw');
        }
        if (Schema::hasTable('users')) {
            Schema::dropIfExists('users');
        }
        if (Schema::hasTable('account')) {
            Schema::dropIfExists('account');
        }

        Schema::create('account', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->decimal('balance', 18, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->string('password', 64);
            $table->char('account_id', 36)->nullable()->unique();
            $table->timestamps();

            $table->foreign('account_id')
                  ->references('id')
                  ->on('account')
                  ->onDelete('set null');
        });

        Schema::create('account_withdraw', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->string('method', 50);
            $table->decimal('amount', 18, 2);
            $table->boolean('scheduled')->default(false);
            $table->dateTime('scheduled_for')->nullable();
            $table->boolean('done')->default(false);
            $table->boolean('error')->default(false);
            $table->string('error_reason', 255)->nullable();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('account')->onDelete('cascade');
        });

        Schema::create('account_withdraw_pix', function (Blueprint $table) {
            $table->uuid('account_withdraw_id')->primary();
            $table->string('type', 50);
            $table->string('key', 255);
            $table->timestamps();

            $table->foreign('account_withdraw_id')->references('id')->on('account_withdraw')->onDelete('cascade');
        });
    }
}
