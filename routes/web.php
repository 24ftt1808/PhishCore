<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScanHistoryController;
use App\Http\Controllers\AnalyticsController;
use App\Models\Analysis;
use App\Models\Report;

Route::get('/', function () {
    $totalScans = Report::where('status', 'completed')->count();
    $threatsDetected = Analysis::whereIn('verdict', ['phishing', 'suspicious'])->count();
    $avgScanSeconds = round((Analysis::avg('duration_ms') ?? 0) / 1000, 1);

    return view('welcome', [
        'totalScans' => $totalScans,
        'threatsDetected' => $threatsDetected,
        'avgScanSeconds' => $avgScanSeconds,
    ]);
})->name('welcome');

Route::get('/scan', [ScanController::class, 'index'])->name('scan.index');
Route::post('/scan', [ScanController::class, 'store'])->name('scan.store');
Route::get('/scan/{report}', [ScanController::class, 'show'])->name('scan.show');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/scan-history', [ScanHistoryController::class, 'index'])->name('scan.history');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
});

require __DIR__.'/auth.php';