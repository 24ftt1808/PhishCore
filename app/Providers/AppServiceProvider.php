<?php

namespace App\Providers;

use App\Models\Analysis;
use App\Models\Report;
use Illuminate\Support\Facades\View;
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
        // live figures for the guest (login / register) side panel, same definitions as the welcome page
        View::composer('layouts.guest', function ($view): void {
            $view->with('guestStats', [
                'scans' => Report::where('status', 'completed')->count(),
                'threats' => Analysis::whereIn('verdict', ['phishing', 'suspicious'])->count(),
                'avgSeconds' => round((Analysis::avg('duration_ms') ?? 0) / 1000, 1),
            ]);
        });
    }
}
