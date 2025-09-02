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
});

require __DIR__.'/auth.php';
