<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Pagination\Paginator::useTailwind();

        \Illuminate\Support\Facades\View::composer('layouts.navigation', function ($view) {
            $view->with('favoritesCount', auth()->check() ? auth()->user()->favorites()->count() : 0);
        });
    }
}
