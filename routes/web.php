<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
});

Route::get('/dashboard', function () {
    $user = Auth::user();

    // Get hunted deals statistics
    $huntedDealsCount = $user->huntedDeals()->count();
    $activeHuntedDealsCount = $user->huntedDeals()->where('is_active', true)->count();

    // Get deals statistics
    $totalDealsCount = \App\Models\Deal::whereHas('huntedDeal', function ($query) use ($user) {
        $query->where('user_id', $user->id);
    })->count();

    $newDealsCount = \App\Models\Deal::whereHas('huntedDeal', function ($query) use ($user) {
        $query->where('user_id', $user->id);
    })->where('created_at', '>=', now()->subDay())->count();

    // Get recent hunted deals with deal counts
    $huntedDeals = $user->huntedDeals()
        ->withCount('deals')
        ->orderBy('updated_at', 'desc')
        ->limit(5)
        ->get();

    // Get recent deals
    $recentDeals = \App\Models\Deal::with('huntedDeal')
        ->whereHas('huntedDeal', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->withFavoriteState($user->id)
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();

    return view('dashboard', compact(
        'huntedDealsCount',
        'activeHuntedDealsCount',
        'totalDealsCount',
        'newDealsCount',
        'huntedDeals',
        'recentDeals'
    ));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Hunted Deals routes
    Route::resource('hunted-deals', App\Http\Controllers\HuntedDealController::class);

    // Deals routes
    Route::resource('deals', App\Http\Controllers\DealController::class)->only(['index', 'show']);

    // Favorites routes
    Route::get('/favorites', [App\Http\Controllers\FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/deals/{deal}/favorite', [App\Http\Controllers\FavoriteController::class, 'toggle'])->name('deals.favorite.toggle');

    // AI Classification routes
    Route::get('/ai-classification', [App\Http\Controllers\AiClassificationController::class, 'index'])->name('ai-classification.index');
    Route::post('/ai-classification/test', [App\Http\Controllers\AiClassificationController::class, 'test'])->name('ai-classification.test');
    Route::post('/ai-classification/test-connection', [App\Http\Controllers\AiClassificationController::class, 'testConnection'])->name('ai-classification.test-connection');

    // Admin routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/crawl-logs', [App\Http\Controllers\AdminController::class, 'crawlLogs'])->name('crawl-logs');
        Route::get('/crawl-logs/{crawlLog}', [App\Http\Controllers\AdminController::class, 'showCrawlLog'])->name('crawl-logs.show');
        Route::get('/system-health', [App\Http\Controllers\AdminController::class, 'systemHealth'])->name('system-health');
        Route::get('/configuration', [App\Http\Controllers\AdminController::class, 'configuration'])->name('configuration');
        Route::post('/trigger-crawl', [App\Http\Controllers\AdminController::class, 'triggerCrawl'])->name('trigger-crawl');
        Route::post('/run-health-check', [App\Http\Controllers\AdminController::class, 'runHealthCheck'])->name('run-health-check');
        Route::post('/cleanup-logs', [App\Http\Controllers\AdminController::class, 'cleanupLogs'])->name('cleanup-logs');
    });
});

// Health check routes (no authentication required)
Route::get('/health', [App\Http\Controllers\HealthController::class, 'check'])->name('health.check');
Route::get('/ping', [App\Http\Controllers\HealthController::class, 'ping'])->name('health.ping');

require __DIR__.'/auth.php';
