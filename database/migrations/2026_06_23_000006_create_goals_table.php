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
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            // Valor-alvo da meta, em centavos (inteiro).
            $table->bigInteger('target_amount');
            // Valor já acumulado para a meta, em centavos (inteiro).
            $table->bigInteger('saved_amount')->default(0);
            $table->date('due_date')->nullable();
            $table->enum('priority', ['baixa', 'media', 'alta'])->default('media');
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
