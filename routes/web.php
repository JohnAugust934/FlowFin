<?php

use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EducationalContentController;
use App\Http\Controllers\Api\GamificationController;
use App\Http\Controllers\Api\GoalController;
use App\Http\Controllers\Api\InsightsController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\InvestmentController;
use App\Http\Controllers\Api\ReportExportController;
use App\Http\Controllers\Api\RecurrenceController;
use App\Http\Controllers\Api\SavingsGoalController;
use App\Http\Controllers\Api\SavingsReportController;
use App\Http\Controllers\Api\SimulatorController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Página de DEMONSTRAÇÃO do design system (uso de desenvolvimento — não é tela de produto).
// Renderiza tema, layout base e biblioteca de componentes para validação visual.
Route::get('/design-system', function () {
    return view('design-system');
})->name('design-system');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Telas de transações e categorias (consomem a API JSON via JS no cliente).
    Route::view('/transacoes', 'transactions.history')->name('transactions.history');
    Route::view('/categorias', 'categories.manage')->name('categories.manage');

    // Pilares de Consciência (insights do mês) e Economia (orçamentos, meta, cortes).
    Route::view('/consciencia', 'insights.consciencia')->name('insights.consciencia');
    Route::view('/economia', 'savings.economia')->name('savings.economia');

    // Pilares de Mentalidade (score, streak, dicas, conteúdos) e Direcionamento (metas, simulador, investimentos).
    Route::view('/mentalidade', 'mindset.mentalidade')->name('mindset.mentalidade');
    Route::view('/direcionamento', 'goals.direcionamento')->name('goals.direcionamento');
});

// API JSON autenticada por sessão (base para a UI e a sincronização offline do PWA).
Route::middleware('auth')->prefix('api')->name('api.')->group(function () {
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::get('/transactions/{id}', [TransactionController::class, 'show'])->name('transactions.show');
    Route::put('/transactions/{id}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{id}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Agregados do dashboard (entrou/saiu/sobrou, por categoria, % necessidade/desejo).
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Recorrências / contas fixas (CRUD + projeção do mês).
    // A rota estática /projection precede /{id} para não ser capturada por ela.
    Route::get('/recurrences/projection', [RecurrenceController::class, 'projection'])->name('recurrences.projection');
    Route::get('/recurrences', [RecurrenceController::class, 'index'])->name('recurrences.index');
    Route::post('/recurrences', [RecurrenceController::class, 'store'])->name('recurrences.store');
    Route::get('/recurrences/{id}', [RecurrenceController::class, 'show'])->name('recurrences.show');
    Route::put('/recurrences/{id}', [RecurrenceController::class, 'update'])->name('recurrences.update');
    Route::delete('/recurrences/{id}', [RecurrenceController::class, 'destroy'])->name('recurrences.destroy');

    // Insights de consciência (top 3, linha do tempo diária, comparativo mês a mês) e gastos invisíveis.
    Route::get('/insights/invisible', [InsightsController::class, 'invisible'])->name('insights.invisible');
    Route::get('/insights', [InsightsController::class, 'index'])->name('insights.index');

    // Orçamentos por categoria (CRUD + status semafórico).
    Route::get('/budgets/status', [BudgetController::class, 'status'])->name('budgets.status');
    Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets.index');
    Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');
    Route::put('/budgets/{id}', [BudgetController::class, 'update'])->name('budgets.update');
    Route::delete('/budgets/{id}', [BudgetController::class, 'destroy'])->name('budgets.destroy');

    // Meta de economia mensal e progresso.
    Route::get('/savings-goal', [SavingsGoalController::class, 'show'])->name('savings-goal.show');
    Route::put('/savings-goal', [SavingsGoalController::class, 'update'])->name('savings-goal.update');

    // Relatório "Onde economizar" (sugestões determinísticas de corte de gastos).
    Route::get('/savings-report', [SavingsReportController::class, 'index'])->name('savings-report.index');

    // Gamificação e direcionamento (Pilar 4 — Mentalidade): Score, streak e dicas.
    Route::get('/score', [GamificationController::class, 'score'])->name('score');
    Route::get('/streak', [GamificationController::class, 'streak'])->name('streak');
    Route::get('/tips', [GamificationController::class, 'tips'])->name('tips');

    // Conteúdos educativos do sistema (lista paginada, filtrável por tema).
    Route::get('/educational-contents', [EducationalContentController::class, 'index'])->name('educational-contents.index');

    // Metas com propósito (Pilar 5) + simulador + prioridades.
    // A rota estática /simulate precede /{id} para não ser capturada por ela.
    Route::post('/goals/simulate', [SimulatorController::class, 'simulate'])->name('goals.simulate');
    Route::get('/goals', [GoalController::class, 'index'])->name('goals.index');
    Route::post('/goals', [GoalController::class, 'store'])->name('goals.store');
    Route::get('/goals/{id}', [GoalController::class, 'show'])->name('goals.show');
    Route::put('/goals/{id}', [GoalController::class, 'update'])->name('goals.update');
    Route::delete('/goals/{id}', [GoalController::class, 'destroy'])->name('goals.destroy');

    // Investimentos (registro simplificado + total agregado).
    Route::get('/investments', [InvestmentController::class, 'index'])->name('investments.index');
    Route::post('/investments', [InvestmentController::class, 'store'])->name('investments.store');
    Route::put('/investments/{id}', [InvestmentController::class, 'update'])->name('investments.update');
    Route::delete('/investments/{id}', [InvestmentController::class, 'destroy'])->name('investments.destroy');

    // Export de relatório mensal (CSV/PDF) e export completo dos dados (LGPD).
    Route::get('/export/monthly', [ReportExportController::class, 'monthly'])->name('export.monthly');
    Route::get('/export/full', [ReportExportController::class, 'full'])->name('export.full');

    // Exclusão definitiva da conta e dos dados pessoais (LGPD — purge físico).
    Route::delete('/account', [AccountController::class, 'destroy'])->name('account.destroy');
});

require __DIR__.'/auth.php';
