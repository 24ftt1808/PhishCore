@php
    $isStuckProcessing = $report->status === 'processing';
@endphp

<div class="relative bg-slate-900/60 border border-sky-500/20 rounded-2xl overflow-hidden mb-8">
    <div class="relative flex items-start gap-4 p-6">
        <span class="w-11 h-11 rounded-xl bg-sky-500/10 border border-sky-500/20 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
        </span>
        <div class="flex-1">
            <p class="text-white font-semibold text-sm mb-1">
                {{ $isStuckProcessing ? 'This scan did not finish' : 'This scan failed to complete' }}
            </p>
            <p class="text-sm text-slate-400 mb-4">
                @if (session('error'))
                    {{ session('error') }}
                @else
                    No analysis result was saved for this report{{ $isStuckProcessing ? ', likely because the scan was interrupted partway through (e.g. a slow connection or a timeout on one of the external checks)' : '' }}.
                @endif
            </p>
            <div class="flex flex-wrap gap-x-6 gap-y-1 text-xs text-slate-500 mb-5">
                <span>Report #{{ $report->id }}</span>
                <span>Type: {{ ucfirst($report->type) }}</span>
                <span>Submitted: {{ $report->created_at->format('Y-m-d H:i') }}</span>
                <span>Status: {{ $report->status }}</span>
            </div>
            <a href="{{ route('scan.index') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-gradient-to-r from-sky-400 to-blue-600 text-white text-sm font-semibold hover:opacity-90 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                Try scanning again
            </a>
        </div>
    </div>
</div>      