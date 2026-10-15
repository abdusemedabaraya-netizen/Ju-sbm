<?php

namespace App\Services;

use App\Models\Report;
use Illuminate\Support\Facades\Hash;

/**
 * Anonymous report tracking: look a report up by its tracking number and check
 * the one-time access code against the stored hash.
 *
 * Access codes are shown grouped for readability (e.g. "ESREK-DR8O1"), so the
 * separators and casing are normalised before the hash comparison.
 */
class ReportTracker
{
    public function find(string $trackingNumber, string $accessCode): ?Report
    {
        $report = Report::where('tracking_number', trim($trackingNumber))->first();

        if (! $report
            || ! $report->access_code_hash
            || ! Hash::check($this->normalize($accessCode), $report->access_code_hash)) {
            return null;
        }

        return $report;
    }

    public function normalize(string $code): string
    {
        return strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $code));
    }
}
