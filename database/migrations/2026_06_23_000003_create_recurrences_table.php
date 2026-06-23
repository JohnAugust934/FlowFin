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
        Schema::create('recurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            // Tipo de movimento gerado pela recorrência: entrada ou saída.
            $table->enum('type', ['entrada', 'saida']);
            // Valor da conta fixa, em centavos (inteiro).
            $table->bigInteger('amount');
            // Periodicidade da recorrência.
            $table->enum('frequency', ['diaria', 'semanal', 'mensal', 'anual'])->default('mensal');
            $table->date('start_date');
            // Próxima data de geração; nula quando a recorrência é encerrada.
            $table->date('next_due_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('category_id');
            $table->index('next_due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurrences');
    }
};
