<?php

namespace App\Http\Controllers;

class ReporterDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $stats = [
            'total'    => $user->reports()->count(),
            'pending'  => $user->reports()->where('status', 'submitted')->count(),
            'inprog'   => $user->reports()->whereIn('status', ['under_review', 'assigned', 'investigating'])->count(),
            'resolved' => $user->reports()->whereIn('status', ['resolved', 'closed'])->count(),
        ];

        $recent      = $user->reports()->with('department')->latest()->limit(5)->get();
        $unreadCount = $user->unreadNotifications()->count();

        return view('reporter.dashboard', compact('stats', 'recent', 'unreadCount'));
    }
}