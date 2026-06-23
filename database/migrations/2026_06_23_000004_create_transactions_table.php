<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            // Recorrência que originou a transação, quando aplicável.
            $table->foreignId('recurrence_id')->nullable()->constrained()->nullOnDelete();
            // Entrada (receita) ou saída (despesa).
            $table->enum('type', ['entrada', 'saida']);
            // Valor da transação, em centavos (inteiro). Nunca decimal/float.
            $table->bigInteger('amount');
            $table->date('date');
            $table->string('description')->nullable();
            // Classificação Necessidade vs. Desejo (aplicável a saídas).
            $table->enum('classification', ['necessidade', 'desejo'])->nullable();
            // Marca transações originadas de contas fixas/recorrências.
            $table->boolean('is_recurring')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('category_id');
            $table->index('date');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
