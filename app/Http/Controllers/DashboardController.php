<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $userId = auth()->id();

        $baseQuery = Analysis::whereHas('report', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        });

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'safe' => (clone $baseQuery)->where('verdict', 'clean')->count(),
            'suspicious' => (clone $baseQuery)->where('verdict', 'suspicious')->count(),
            'phishing' => (clone $baseQuery)->where('verdict', 'phishing')->count(),
        ];

        return view('dashboard', ['stats' => $stats]);
    }
}