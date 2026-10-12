<?php

namespace App\Http\Controllers;

use App\Models\Investigation;

class InvestigatorCaseController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasRole('Investigator'), 403);

        $cases = Investigation::with('report:id,tracking_number,subject,status,priority')
            ->where('investigator_id', auth()->id())
            ->latest()
            ->get();

        $stats = [
            'total'    => $cases->count(),
            'assigned' => $cases->filter(fn ($c) => $c->report?->status === 'assigned')->count(),
            'ongoing'  => $cases->filter(fn ($c) => $c->report?->status === 'investigating')->count(),
            'resolved' => $cases->filter(fn ($c) => in_array($c->report?->status, ['resolved', 'closed'], true))->count(),
        ];

        return view('cases.mine', compact('cases', 'stats'));
    }
}