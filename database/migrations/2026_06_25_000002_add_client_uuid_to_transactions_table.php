<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotência server-side da criação de transações.
 *
 * `client_uuid` é a chave de idempotência gerada no cliente (camada offline, Task 5.2):
 * o mesmo `client_uuid` nunca pode originar duas transações. Coluna NULLABLE — transações
 * antigas (e qualquer criação sem a chave) ficam com valor nulo, sem perda de dados.
 *
 * Unique COMPOSTO (user_id, client_uuid): o escopo por usuário é o correto porque a chave
 * é gerada no dispositivo do usuário; um UUID coincidente de outro usuário nunca deve
 * bloquear a criação. No MySQL múltiplos NULLs são permitidos num índice unique, então
 * transações sem a chave não conflitam entre si.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->after('id');
            $table->unique(['user_id', 'client_uuid'], 'transactions_user_client_uuid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique('transactions_user_client_uuid_unique');
            $table->dropColumn('client_uuid');
        });
    }
};
