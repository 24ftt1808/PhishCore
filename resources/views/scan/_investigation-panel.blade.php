@php
    $investigation = $report->investigation;
    $isTeamMember = auth()->user()->is_team_member;

       $invStatusStyles = [
        'active' => ['badge' => 'text-sky-400', 'label' => 'ACTIVE'],
        'completed' => ['badge' => 'text-emerald-400', 'label' => 'COMPLETED'],
        'takedown_requested' => ['badge' => 'text-orange-400', 'label' => 'REPORTED TO BRUCERT'],
        'takedown_confirmed' => ['badge' => 'text-emerald-400', 'label' => 'TAKEDOWN CONFIRMED BY BRUCERT'],
    ];

    $invDotColors = [
        'active' => 'bg-sky-400',
        'completed' => 'bg-emerald-400',
        'takedown_requested' => 'bg-orange-400',
        'takedown_confirmed' => 'bg-emerald-400',
    ];
@endphp

@if (!$isTeamMember)
    <div class="r-card r-in p-6 mb-8 mt-6">
        <div class="flex items-center justify-between flex-wrap gap-3 {{ !$investigation ? 'mb-5' : '' }}">
            <div class="flex items-center gap-3">
                <div>
                    <h2 class="text-lg font-bold text-white">Investigation Status</h2>
                    <p class="text-sm text-slate-300">
                        {{ $investigation ? 'This report is being tracked by our team.' : 'Think this needs closer attention? Let our team know.' }}
                    </p>
                </div>
            </div>
                     @if ($investigation)
                <span class="text-xs font-semibold {{ $invStatusStyles[$investigation->status]['badge'] }}">
                    {{ $invStatusStyles[$investigation->status]['label'] }}
                </span>
            @endif
        </div>

               @if ($investigation && $investigation->statusLogs->isNotEmpty())
            <div class="mt-5 mb-5">
                <p class="text-xs text-slate-300 mb-3">STATUS TIMELINE</p>
                               <div class="space-y-0">
                    @foreach ($investigation->statusLogs as $log)
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0 mt-1.5 {{ $invDotColors[$log->status] }}"></span>
                                @unless ($loop->last)
                                    <span class="w-px flex-1 bg-slate-600/50 my-1"></span>
                                @endunless
                            </div>
                            <div class="pb-6">
                                <p class="text-sm text-slate-100">{{ $invStatusStyles[$log->status]['label'] }}</p>
                                <p class="text-xs text-slate-300">{{ $log->created_at->format('j F Y \a\t g:i A') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if (!$investigation)
            @if (session('success'))
                <div class="mb-4 text-sm text-emerald-300 bg-emerald-500/10 border border-emerald-500/20 rounded-lg px-4 py-2.5">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 text-sm text-red-300 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">
                    {{ session('error') }}
                </div>
            @endif
            <form method="POST" action="{{ route('investigations.request', $report) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs text-slate-300 mb-1.5">NOTES (OPTIONAL)</label>
                    <textarea name="notes" rows="2" placeholder="Anything you'd like our team to know about this report..."
                        class="w-full r-well px-4 py-2.5 text-sm text-slate-300 placeholder:text-slate-400 focus:outline-none focus:border-slate-400/60"></textarea>
                </div>
                <button type="submit"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-sky-400 to-blue-600 hover:opacity-90 text-white text-sm font-semibold transition">
                    Request Investigation
                </button>
            </form>
        @endif
    </div>
@else
    {{-- FULL MANAGEMENT VIEW for team members --}}
    <div class="r-card r-in p-6 mb-8 mt-6">
        <div class="flex items-center justify-between flex-wrap gap-3 mb-5">
            <div class="flex items-center gap-3">
                <div>
                    <h2 class="text-lg font-bold text-white">Investigation</h2>
                    <p class="text-sm text-slate-300">Track this report. BruCERT handles the takedown.</p>
                </div>
            </div>
            @if ($investigation)
                <span class="text-xs font-semibold {{ $invStatusStyles[$investigation->status]['badge'] }}">
                    {{ $invStatusStyles[$investigation->status]['label'] }}
                </span>
            @endif
        </div>

        @if (session('success'))
            <div class="mb-4 text-sm text-emerald-300 bg-emerald-500/10 border border-emerald-500/20 rounded-lg px-4 py-2.5">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 text-sm text-red-300 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">
                {{ session('error') }}
            </div>
        @endif

        @if (!$investigation)
            <form method="POST" action="{{ route('investigations.store', $report) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs text-slate-300 mb-1.5">ASSIGN TO (OPTIONAL)</label>
                    <select name="assigned_to"
                        class="w-full r-well px-4 py-2.5 text-sm text-slate-300 focus:outline-none focus:border-slate-400/60">
                        <option value="">Unassigned</option>
                        @foreach (\App\Models\User::where(fn ($q) => $q->where('is_team_member', true)->orWhere('role', 'admin'))->orderBy('name')->get() as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-slate-300 mb-1.5">NOTES (OPTIONAL)</label>
                    <textarea name="notes" rows="2" placeholder="Add any initial notes about this case..."
                        class="w-full r-well px-4 py-2.5 text-sm text-slate-300 placeholder:text-slate-400 focus:outline-none focus:border-slate-400/60"></textarea>
                </div>
                <button type="submit"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-sky-400 to-blue-600 hover:opacity-90 text-white text-sm font-semibold transition">
                    Open Investigation
                </button>
            </form>
        @else
            <dl class="grid sm:grid-cols-2 gap-4 text-sm mb-5">
                <div>
                    <dt class="text-xs text-slate-300 mb-1">ASSIGNED TO</dt>
                    <dd class="text-slate-300">{{ $investigation->assignedUser?->name ?? 'Unassigned' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-300 mb-1">OPENED</dt>
                    <dd class="text-slate-300">{{ $investigation->created_at->format('j F Y \a\t g:i A') }}</dd>
                </div>
                @if ($investigation->resolved_at)
                    <div>
                        <dt class="text-xs text-slate-300 mb-1">RESOLVED</dt>
                        <dd class="text-slate-300">{{ $investigation->resolved_at->format('j F Y \a\t g:i A') }}</dd>
                    </div>
                @endif
                             @if ($investigation->notes)
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-slate-300 mb-1">NOTES</dt>
                        <dd class="text-slate-300">{{ $investigation->notes }}</dd>
                    </div>
                @endif
            </dl>

            @if ($investigation->statusLogs->isNotEmpty())
                <div class="mb-5">
                    <p class="text-xs text-slate-300 mb-3">STATUS TIMELINE</p>
                                 <div class="space-y-0">
                    @foreach ($investigation->statusLogs as $log)
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0 mt-1.5 {{ $invDotColors[$log->status] }}"></span>
                                @unless ($loop->last)
                                    <span class="w-px flex-1 bg-slate-600/50 my-1"></span>
                                @endunless
                            </div>
                            <div class="pb-6">
                                <p class="text-sm text-slate-100">{{ $invStatusStyles[$log->status]['label'] }}</p>
                                <p class="text-xs text-slate-300">
                                    {{ $log->created_at->format('j F Y \a\t g:i A') }}
                                    @if ($log->changedBy)
                                        &middot; by {{ $log->changedBy->name }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endforeach
                    </div>
                </div>
            @endif

            @php
                // The email to BruCERT. The item is written "defanged" (hxxp, [.]) so nobody opens it by accident.
                $brucertEmail = (string) config('brucert.email');
                $flagged = $analysis ?? $report->analyses->first();
                $verdictWord = $flagged?->verdict === 'phishing' ? 'phishing' : 'suspicious';
                $proofLink = ($report->type === 'url' && $report->status === 'completed' && $flagged && in_array($flagged->verdict, ['suspicious', 'phishing'], true))
                    ? route('reports.public.show', $report)
                    : null;
                $reportedItem = (string) match ($report->type) {
                    'email' => $report->sender_email,
                    'phone' => $report->phone_number,
                    'file' => 'Uploaded PDF file (SHA-256 '.$report->file_sha256.')',
                    'screenshot' => 'A screenshot submitted to PhishCore',
                    default => $report->url,
                };
                $defanged = str_replace(['http://', 'https://', '.'], ['hxxp://', 'hxxps://', '[.]'], $reportedItem);
                $mailSubject = 'PhishCore report for takedown: '.$defanged;
                $mailBody = "Hello BruCERT,\n\nPhishCore flagged the following as {$verdictWord}.\n\n"
                    ."Item: {$defanged}\n"
                    .'Type: '.$report->type."\n"
                    .'First scanned: '.$report->created_at->format('j F Y, g:i A')."\n"
                    .'PhishCore report number: '.$report->id."\n"
                    .'Evidence: '.($proofLink ?? 'No public evidence page for this type of report.')."\n\n"
                    ."The item is written in a safe form (hxxp, [.]) so it cannot be opened by accident.\n"
                    ."Please review it for takedown.\n\nThank you,\nPhishCore team\nphishcorebn@gmail.com";
                $mailto = 'mailto:'.$brucertEmail.'?subject='.rawurlencode($mailSubject).'&body='.rawurlencode($mailBody);
            @endphp
            <div class="r-well p-4 mb-5" data-brucert-card>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-white">Report to BruCERT</h3>
                        <p class="text-xs text-slate-300 mt-1 leading-relaxed">BruCERT handles takedowns, not PhishCore. Email them this report, then set the status below to &ldquo;Reported to BruCERT&rdquo;.</p>
                    </div>
                    <a href="{{ $mailto }}" data-brucert-mail
                       class="px-4 py-2 rounded-xl bg-gradient-to-r from-sky-400 to-blue-600 hover:opacity-90 text-white text-sm font-semibold transition whitespace-nowrap">Email BruCERT</a>
                </div>
                @if ($proofLink)
                    <label class="block text-xs text-slate-300 mt-4 mb-1.5">EVIDENCE LINK (PUBLIC, NO LOGIN NEEDED)</label>
                    <input type="text" readonly value="{{ $proofLink }}" onclick="this.select()" data-brucert-proof
                           class="r-well w-full px-3 py-2 text-xs font-mono text-slate-200 focus:outline-none">
                @else
                    <p class="text-xs text-slate-400 mt-3">Only website reports have a public evidence page, so this email has no evidence link.</p>
                @endif
                @if ($brucertEmail === '')
                    <p class="text-xs text-slate-400 mt-3">No BruCERT address is set yet. Add BRUCERT_EMAIL to the .env file, or type the address in your email app.</p>
                @endif
            </div>

            <form method="POST" action="{{ route('investigations.update', $investigation) }}" class="flex flex-wrap items-end gap-3">
                @csrf
                @method('PATCH')
                              <div>
                    <label class="block text-xs text-slate-300 mb-1.5">UPDATE STATUS</label>
                    <select name="status"
                        class="r-well pl-4 pr-9 py-2.5 text-sm text-slate-300 focus:outline-none focus:border-slate-400/60 min-w-[210px]">
                        @foreach (['active', 'completed', 'takedown_requested', 'takedown_confirmed'] as $statusOption)
                            <option value="{{ $statusOption }}" @selected($investigation->status === $statusOption)>
                                {{ $invStatusStyles[$statusOption]['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-slate-300 mb-1.5">REASSIGN TO</label>
                    <select name="assigned_to"
                        class="r-well px-4 py-2.5 text-sm text-slate-300 focus:outline-none focus:border-slate-400/60">
                        <option value="">Unassigned</option>
                        @foreach (\App\Models\User::where(fn ($q) => $q->where('is_team_member', true)->orWhere('role', 'admin'))->orderBy('name')->get() as $user)
                            <option value="{{ $user->id }}" @selected($investigation->assigned_to === $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                    class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-sky-400 to-blue-600 hover:opacity-90 text-white text-sm font-semibold transition">
                    Update
                </button>
            </form>
        @endif
    </div>
@endif