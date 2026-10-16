<x-layouts.dashboard>
    <style>
        .h-card { background: linear-gradient(180deg, rgba(24, 40, 76, .62), rgba(14, 25, 50, .62)); border: 1px solid rgba(148, 163, 184, .18); border-radius: 1.1rem; box-shadow: inset 0 1px 0 rgba(255, 255, 255, .05); }
        .h-field { background: rgba(8, 15, 32, .55); border: 1px solid rgba(148, 163, 184, .2); border-radius: .75rem; color: #e2e8f0; transition: border-color .15s; color-scheme: dark; }
        .h-field::placeholder { color: #94a3b8; }
        .h-field:focus { outline: none; border-color: #38bdf8; }
        .h-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; padding: .5rem .9rem; border-radius: .75rem; font-size: .8125rem; font-weight: 600; transition: background-color .15s, border-color .15s; }
        .h-btn-main { color: #fff; background: linear-gradient(90deg, #38bdf8, #2563eb); }
        .h-btn-danger { color: #fca5a5; border: 1px solid rgba(248, 113, 113, .4); background: rgba(248, 113, 113, .08); }
        .h-btn-danger:hover { background: rgba(248, 113, 113, .18); }
        .h-row { transition: background-color .15s; }
        .h-row:hover { background-color: rgba(125, 211, 252, .06); }
        .h-in { animation: h-in .45s cubic-bezier(.2, .9, .3, 1) both; animation-delay: var(--d, 0s); }
        @keyframes h-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { .h-in { animation: none; } }
    </style>

    <div class="h-in mb-5 sm:mb-6">
        <h1 class="text-2xl font-bold text-white mb-1">Safe Files</h1>
        <p class="text-slate-300 text-sm">Files an administrator has checked and marked as safe, for example a real app a scan called suspicious by mistake. A scan of the exact same file shows it as safe. Only the file's fingerprint and name are kept, never the file.@unless ($canRemove) Only administrators can remove a safe mark.@endunless</p>
    </div>

    @if (session('status'))
        <p class="h-in mb-4 rounded-xl border border-emerald-300/25 bg-emerald-400/[0.07] px-4 py-3 text-sm text-emerald-200" role="status">{{ session('status') }}</p>
    @endif

    <div class="h-in h-card p-3.5 sm:p-5 mb-5 inline-block min-w-[10rem]" style="--d:.06s">
        <p class="text-[11px] sm:text-xs font-medium tracking-wide text-slate-300 mb-1">FILES MARKED SAFE</p>
        <p class="text-2xl sm:text-3xl font-bold tabular-nums text-emerald-300">{{ $total }}</p>
    </div>

    <form method="GET" action="{{ route('safe-files.index') }}" class="h-in h-card p-4 mb-5 flex gap-2" style="--d:.1s">
        <input type="text" name="search" value="{{ $search }}" placeholder="Search by file name or fingerprint" autocomplete="off" class="h-field flex-1 min-w-0 px-4 py-2.5 text-sm">
        <button type="submit" class="h-btn h-btn-main">Search</button>
    </form>

    <div class="h-in h-card overflow-hidden" style="--d:.14s">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead>
                    <tr class="border-b border-slate-500/25 text-left text-xs font-medium tracking-wide text-slate-300">
                        <th class="px-4 sm:px-5 py-3">FILE</th>
                        <th class="px-4 sm:px-5 py-3">FINGERPRINT</th>
                        <th class="px-4 sm:px-5 py-3">MARKED BY</th>
                        <th class="px-4 sm:px-5 py-3">WHEN</th>
                        @if ($canRemove)
                            <th class="px-4 sm:px-5 py-3 text-right">SAFE MARK</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-500/20">
                    @forelse ($files as $file)
                        <tr class="h-row">
                            <td class="px-4 sm:px-5 py-3 text-slate-100 break-all max-w-[16rem]">{{ $file->file_name ?: 'Unnamed file' }}</td>
                            <td class="px-4 sm:px-5 py-3 font-mono text-xs text-slate-300" title="{{ $file->file_sha256 }}">{{ substr($file->file_sha256, 0, 16) }}…</td>
                            <td class="px-4 sm:px-5 py-3 text-slate-300">{{ $file->markedByUser?->name ?? 'Removed user' }}</td>
                            <td class="px-4 sm:px-5 py-3 text-slate-300" title="{{ $file->created_at->format('j F Y \a\t g:i A') }}">{{ $file->created_at->diffForHumans() }}</td>
                            @if ($canRemove)
                                <td class="px-4 sm:px-5 py-3 text-right">
                                    <form method="POST" action="{{ route('safe-files.destroy', $file) }}"
                                          onsubmit="return confirm('Remove the safe mark from this file? New scans of it will be checked as normal again.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="h-btn h-btn-danger">Remove</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canRemove ? 5 : 4 }}" class="px-5 py-10 text-center text-slate-300">
                                {{ $search !== '' ? 'No safe file matches that search.' : 'No file has been marked as safe yet.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.dashboard>