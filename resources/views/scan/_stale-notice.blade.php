{{-- A scan is a snapshot: report counts, "x ago" wording and blacklist results can change after it ran. --}}
@if ($hasAnalysis && $report->created_at->lt(now()->subDay()))
    <div class="r-in mb-6 flex flex-col sm:flex-row sm:items-center gap-3 rounded-xl border border-amber-300/25 bg-amber-400/[0.07] px-4 py-3 text-sm text-amber-100" style="--d:.12s" role="note">
        <div class="flex items-start gap-2.5 flex-1">
            <svg class="w-4 h-4 mt-0.5 shrink-0 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>
                <span class="font-semibold">Saved result from {{ $report->created_at->format('j F Y') }} ({{ $report->created_at->diffForHumans() }}).</span>
                Report counts, website status and security-vendor results can change, so this may no longer be up to date.
            </span>
        </div>
        <a href="{{ route('scan.index') }}" class="shrink-0 inline-flex items-center justify-center gap-1.5 rounded-lg border border-amber-300/30 bg-amber-400/10 px-3 py-1.5 text-xs font-semibold text-amber-100 hover:bg-amber-400/20 transition">Scan again</a>
    </div>
@endif