<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/scan', [ScanController::class, 'index'])->name('scan.index');
Route::post('/scan', [ScanController::class, 'store'])->name('scan.store');
Route::get('/scan/{report}', [ScanController::class, 'show'])->name('scan.show');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';