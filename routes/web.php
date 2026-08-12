<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AiClassificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HuntedDealController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Hunted Deals routes
    Route::resource('hunted-deals', HuntedDealController::class);

    // Deals routes
    Route::resource('deals', DealController::class)->only(['index', 'show']);

    // Favorites routes
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/deals/{deal}/favorite', [FavoriteController::class, 'toggle'])->name('deals.favorite.toggle');

    // AI Classification routes
    Route::get('/ai-classification', [AiClassificationController::class, 'index'])->name('ai-classification.index');
    Route::post('/ai-classification/test', [AiClassificationController::class, 'test'])->name('ai-classification.test');
    Route::post('/ai-classification/test-connection', [AiClassificationController::class, 'testConnection'])->name('ai-classification.test-connection');

    // Admin routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/crawl-logs', [AdminController::class, 'crawlLogs'])->name('crawl-logs');
        Route::get('/crawl-logs/{crawlLog}', [AdminController::class, 'showCrawlLog'])->name('crawl-logs.show');
        Route::get('/system-health', [AdminController::class, 'systemHealth'])->name('system-health');
        Route::get('/configuration', [AdminController::class, 'configuration'])->name('configuration');
        Route::post('/trigger-crawl', [AdminController::class, 'triggerCrawl'])->name('trigger-crawl');
        Route::post('/run-health-check', [AdminController::class, 'runHealthCheck'])->name('run-health-check');
        Route::post('/cleanup-logs', [AdminController::class, 'cleanupLogs'])->name('cleanup-logs');
    });
});

// Health check routes (no authentication required)
Route::get('/health', [HealthController::class, 'check'])->name('health.check');
Route::get('/ping', [HealthController::class, 'ping'])->name('health.ping');

require __DIR__.'/auth.php';
