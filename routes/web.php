<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ChatPageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvestigationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NumberReportController;
use App\Http\Controllers\PagePreviewController;
use App\Http\Controllers\PlayController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicFeedController;
use App\Http\Controllers\PublicReportsController;
use App\Http\Controllers\ReportedNumbersController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\ScamNewsController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\ScanHistoryController;
use App\Http\Controllers\ScanPdfController;
use App\Http\Controllers\ScanShareController;
use App\Http\Controllers\SharedScanController;
use App\Http\Controllers\TrustedFileController;
use App\Http\Controllers\UserManagementController;
use App\Models\Analysis;
use App\Models\Report;
use Illuminate\Support\Facades\Route;

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

Route::view('/terms', 'legal.terms')->name('terms');
Route::view('/privacy', 'legal.privacy')->name('privacy');

Route::get('/scan', [ScanController::class, 'index'])->name('scan.index');
Route::post('/scan', [ScanController::class, 'store'])->middleware('throttle:60,1')->name('scan.store');
Route::get('/scan/{report}', [ScanController::class, 'show'])->name('scan.show');
Route::get('/scan/{report}/pdf', ScanPdfController::class)->name('scan.pdf');

// Optional "Show page preview" button (404 until PAGE_PREVIEW=true). Only people who can open the result may use it.
// The page calls it every few seconds while urlscan.io is taking the picture.
Route::post('/scan/{report}/preview', PagePreviewController::class)
    ->middleware('throttle:30,1')
    ->name('scan.preview');

// Opt-in, revocable share links for finished link scans.
Route::post('/scan/{report}/share', [ScanShareController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('scan.share.store');
Route::delete('/scan/{report}/share', [ScanShareController::class, 'destroy'])
    ->middleware('throttle:20,1')
    ->name('scan.share.destroy');
Route::get('/r/{token}', [SharedScanController::class, 'show'])
    ->where('token', '[A-Za-z0-9]{40}')
    ->middleware('throttle:60,1')
    ->name('scan.shared');

Route::get('/public-reports', [PublicReportsController::class, 'index'])->name('reports.public');
Route::get('/public-reports/feed.{format}', PublicFeedController::class)
    ->where('format', 'csv|json|txt')
    ->middleware('throttle:30,1')
    ->name('reports.feed');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::patch('/profile/preferences', [ProfileController::class, 'updatePreferences'])->name('profile.preferences.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfileController::class, 'removePhoto'])->name('profile.photo.destroy');

    Route::post('/scan/{report}/report-number', [NumberReportController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('number-report.store');

    Route::get('/chat', ChatPageController::class)->name('chat.index');
    Route::get('/play', PlayController::class)->name('play.index');
    Route::post('/play/progress', [PlayController::class, 'store'])->middleware('throttle:60,1')->name('play.progress');
    Route::post('/chat', ChatController::class)->middleware('throttle:15,1')->name('chat.send');

    Route::get('/scan-history', [ScanHistoryController::class, 'index'])->name('scan.history');
    Route::get('/scan-history/export', [ScanHistoryController::class, 'export'])->middleware('throttle:10,1')->name('scan.history.export');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    Route::get('/analytics/export', [AnalyticsController::class, 'export'])->middleware('throttle:10,1')->name('analytics.export');

    Route::post('/investigations/{report}', [InvestigationController::class, 'store'])->name('investigations.store');
    Route::post('/investigations/{report}/request', [InvestigationController::class, 'request'])->name('investigations.request');
    Route::patch('/investigations/{investigation}', [InvestigationController::class, 'update'])->name('investigations.update');
    Route::get('/investigations', [InvestigationController::class, 'index'])->name('investigations.index');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/notifications/{id}', [NotificationController::class, 'open'])->name('notifications.open');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('/notifications', [NotificationController::class, 'clear'])->name('notifications.clear');

    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');

    // Admin page for the dashboard's "Latest Scam News" box (team members and administrators only).
    Route::get('/scam-news', [ScamNewsController::class, 'index'])->name('scam-news.index');
    Route::post('/scam-news', [ScamNewsController::class, 'store'])->middleware('throttle:30,1')->name('scam-news.store');
    Route::post('/scam-news/fetch', [ScamNewsController::class, 'fetch'])->middleware('throttle:6,1')->name('scam-news.fetch');
    Route::patch('/scam-news/{scamNews}', [ScamNewsController::class, 'toggle'])->name('scam-news.toggle');
    Route::delete('/scam-news/{scamNews}', [ScamNewsController::class, 'destroy'])->name('scam-news.destroy');

    // Admin page for the public "Most reported phone numbers" list (team members and administrators only).
    Route::get('/reported-numbers', [ReportedNumbersController::class, 'index'])->name('reported-numbers.index');
    Route::post('/reported-numbers/hide', [ReportedNumbersController::class, 'hide'])->middleware('throttle:60,1')->name('reported-numbers.hide');
    Route::post('/reported-numbers/show', [ReportedNumbersController::class, 'show'])->middleware('throttle:60,1')->name('reported-numbers.show');

    // Administrators can mark the file behind a scan as safe (or take the mark off again), and see every safe file.
    Route::post('/scan/{report}/trust-file', [TrustedFileController::class, 'store'])->middleware('throttle:30,1')->name('scan.trust-file');
    Route::delete('/scan/{report}/trust-file', [TrustedFileController::class, 'destroy'])->middleware('throttle:30,1')->name('scan.untrust-file');
    Route::get('/safe-files', [TrustedFileController::class, 'index'])->name('safe-files.index');
    Route::delete('/safe-files/{trustedFile}', [TrustedFileController::class, 'destroyFile'])->middleware('throttle:30,1')->name('safe-files.destroy');

    Route::get('/user-management', [UserManagementController::class, 'index'])->name('user-management.index');
    Route::patch('/user-management/{user}', [UserManagementController::class, 'update'])->name('user-management.update');
    Route::post('/user-management/{user}/toggle-suspend', [UserManagementController::class, 'toggleSuspend'])->name('user-management.toggle-suspend');
});

require __DIR__.'/auth.php';
