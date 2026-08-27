<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScanHistoryController;
use App\Http\Controllers\AnalyticsController;
use App\Models\Analysis;
use App\Models\Report;
use App\Http\Controllers\InvestigationController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\UserManagementController;

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
    Route::post('/investigations/{report}', [InvestigationController::class, 'store'])->name('investigations.store');
    Route::post('/investigations/{report}/request', [InvestigationController::class, 'request'])->name('investigations.request');
    Route::patch('/investigations/{investigation}', [InvestigationController::class, 'update'])->name('investigations.update');
    Route::get('/investigations', [InvestigationController::class, 'index'])->name('investigations.index');
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/user-management', [UserManagementController::class, 'index'])->name('user-management.index');
Route::patch('/user-management/{user}', [UserManagementController::class, 'update'])->name('user-management.update');
Route::post('/user-management/{user}/toggle-suspend', [UserManagementController::class, 'toggleSuspend'])->name('user-management.toggle-suspend');
Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
Route::delete('/profile/photo', [ProfileController::class, 'removePhoto'])->name('profile.photo.destroy');
});


require __DIR__.'/auth.php';