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
        Schema::table('users', function (Blueprint $table) {
            // Sequência atual de dias consecutivos com registro de transação.
            // Snapshot mantido pelo scheduler (job diário); o valor "ao vivo" é
            // recalculado pelo StreakService a partir das transações.
            $table->unsignedInteger('current_streak')->default(0)->after('monthly_savings_goal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('current_streak');
        });
    }
};
