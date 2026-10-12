<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>{{ $meta['title'] }} | {{ config('jiacrs.site_name', 'JIACRS') }}</title>
  <style>
    body { font-family: Arial, Helvetica, sans-serif; color: #111; margin: 24px; font-size: 13px; }
    h1 { font-size: 20px; margin: 0 0 4px; }
    .muted { color: #555; }
    table { border-collapse: collapse; width: 100%; margin: 12px 0 20px; }
    th, td { border: 1px solid #bbb; padding: 5px 7px; text-align: left; vertical-align: top; }
    th { background: #eef2f7; }
    .summary td { width: 25%; }
    .no-print button { padding: 8px 14px; margin-bottom: 14px; cursor: pointer; }
    @media print { .no-print { display: none; } body { margin: 8mm; } }
  </style>
</head>
<body>
  <div class="no-print"><button onclick="window.print()">Print / Save as PDF</button></div>

  <h1>Jimma University &mdash; Integrity and Anti-Corruption Reporting System</h1>
  <div><strong>{{ $meta['label'] }}</strong></div>
  <div class="muted">Generated {{ now()->format('d M Y H:i') }} by {{ auth()->user()->name }}</div>

  <h3>Summary</h3>
  <table class="summary">
    <tr><th>Total reports</th><td colspan="3">{{ $summary['total'] }}</td></tr>
    <tr>
      <th>By status</th>
      <td colspan="3">
        @forelse ($summary['status'] as $k => $v){{ ucwords(str_replace('_', ' ', $k)) }}: {{ $v }}@if(! $loop->last) &middot; @endif
        @empty — @endforelse
      </td>
    </tr>
    <tr>
      <th>By priority</th>
      <td colspan="3">
        @forelse ($summary['priority'] as $k => $v){{ ucfirst($k) }}: {{ $v }}@if(! $loop->last) &middot; @endif
        @empty — @endforelse
      </td>
    </tr>
  </table>

  <h3>Reports</h3>
  <table>
    <thead>
      <tr><th>Tracking no.</th><th>Subject</th><th>Category</th><th>Department</th><th>Priority</th><th>Status</th><th>Submitted</th></tr>
    </thead>
    <tbody>
      @forelse ($rows as $r)
        <tr>
          <td>{{ $r->tracking_number }}</td>
          <td>{{ $r->subject }}</td>
          <td>{{ $r->category }}</td>
          <td>{{ $r->department }}</td>
          <td>{{ ucfirst($r->priority) }}</td>
          <td>{{ ucwords(str_replace('_', ' ', $r->status)) }}</td>
          <td>{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('d M Y') }}</td>
        </tr>
      @empty
        <tr><td colspan="7" class="muted">No reports in this period.</td></tr>
      @endforelse
    </tbody>
  </table>

  <p class="muted">Confidential. Reporter identities are not included in this document.</p>
</body>
</html>
