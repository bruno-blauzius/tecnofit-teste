<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->string('password', 64); // SHA256 = 64 caracteres
            $table->char('account_id', 36)->nullable()->unique();
            $table->timestamps();

            // Foreign key para account (relacionamento 1:1)
            $table->foreign('account_id')
                  ->references('id')
                  ->on('account')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
}
