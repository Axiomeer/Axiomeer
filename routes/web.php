<?php

use App\Http\Controllers\AgentPipelineController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QueryController;
use App\Http\Controllers\ResponsibleAiController;
use App\Http\Controllers\SafetyTestController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

// Guest redirect to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Dashboard (all roles)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Query (all roles)
    Route::resource('query', QueryController::class)->only(['index', 'store', 'show']);

    // Documents (all roles)
    Route::resource('documents', DocumentController::class)->except(['edit', 'update']);

    // Responsible AI (all roles)
    Route::get('/responsible-ai', [ResponsibleAiController::class, 'index'])->name('responsible-ai');

    // Analytics (admin + analyst)
    Route::middleware('role:admin,analyst')->group(function () {
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
        Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log');
        Route::get('/evaluation', [EvaluationController::class, 'index'])->name('evaluation');
    });

    // System (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::post('/settings/domains', [SettingsController::class, 'storeDomain'])->name('settings.domains.store');
        Route::put('/settings/domains/{domain}', [SettingsController::class, 'updateDomain'])->name('settings.domains.update');
        Route::delete('/settings/domains/{domain}', [SettingsController::class, 'destroyDomain'])->name('settings.domains.destroy');
        Route::get('/agents', [AgentPipelineController::class, 'index'])->name('agents');
        Route::get('/safety-test', [SafetyTestController::class, 'index'])->name('safety-test');
        Route::post('/safety-test/run', [SafetyTestController::class, 'run'])->name('safety-test.run');
    });
});

// API endpoints (authenticated)
Route::middleware('auth')->prefix('api')->group(function () {
    Route::get('/speech-token', \App\Http\Controllers\Api\SpeechTokenController::class)->name('api.speech-token');
    Route::post('/web-search', [\App\Http\Controllers\Api\WebSearchController::class, 'search'])->name('api.web-search');
});

require __DIR__.'/auth.php';
