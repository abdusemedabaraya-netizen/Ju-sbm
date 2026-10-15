@props(['histories', 'title' => 'Status history'])

{{-- Shared status timeline used by the Report page and the Case workspace. --}}
<div class="card mb-3">
  <div class="card-header"><h3 class="card-title mb-0">{{ $title }}</h3></div>
  <div class="card-body">
    @forelse ($histories as $history)
      <div class="d-flex {{ $loop->last ? '' : 'mb-3' }}">
        <div class="me-3"><i class="bi bi-clock-history text-primary"></i></div>
        <div class="flex-grow-1 border-bottom pb-2">
          <div class="d-flex justify-content-between">
            <span>
              @if ($history->from_status)
                <x-status-badge :status="$history->from_status" />
                <i class="bi bi-arrow-right mx-1"></i>
              @endif
              <x-status-badge :status="$history->to_status" />
            </span>
            <small class="text-secondary">{{ optional($history->created_at)->format('d M Y H:i') }}</small>
          </div>
          <div class="small text-secondary mt-1">
            by {{ $history->changedBy?->name ?? 'System' }}
            @if ($history->note) — {{ $history->note }} @endif
          </div>
        </div>
      </div>
    @empty
      <p class="text-secondary mb-0">No status history recorded.</p>
    @endforelse
  </div>
</div>
