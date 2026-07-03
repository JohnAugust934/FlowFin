<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hardening de performance (Task 5.5).
 *
 * As consultas que alimentam o dashboard, os insights, o relatório "onde economizar"
 * e o Score filtram sempre pelo mesmo padrão: user_id (igualdade) + type (igualdade)
 * + date (faixa do mês). Os índices de coluna única existentes só permitem ao MySQL
 * usar UM por consulta. Este índice COMPOSTO (user_id, type, date) cobre o padrão
 * dominante — colunas de igualdade antes da coluna de faixa — reduzindo as varreduras.
 *
 * Migration ADITIVA: não remove os índices existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['user_id', 'type', 'date'], 'transactions_user_type_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_user_type_date_index');
        });
    }
};
