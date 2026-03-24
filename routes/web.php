<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QueryController;
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

    // Analytics (admin + analyst)
    Route::middleware('role:admin,analyst')->group(function () {
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
        Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log');
        Route::get('/evaluation', [EvaluationController::class, 'index'])->name('evaluation');
    });

    // System (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/settings', fn () => view('pages.coming-soon', ['page' => 'Settings']))->name('settings');
        Route::get('/agents', fn () => view('pages.coming-soon', ['page' => 'Agent Pipeline']))->name('agents');
    });
});

require __DIR__.'/auth.php';
