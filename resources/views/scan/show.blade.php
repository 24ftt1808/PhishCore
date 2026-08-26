@php
    $hasAnalysis = $analysis !== null;

    if ($hasAnalysis) {
        $verdictStyles = [
            'phishing' => ['bg' => 'bg-red-500/10', 'border' => 'border-red-500/20', 'text' => 'text-red-400', 'ring' => '#f87171', 'label' => 'PHISHING DETECTED', 'threat' => 'HIGH THREAT', 'threatBg' => 'bg-red-500/20 text-red-300'],
            'suspicious' => ['bg' => 'bg-orange-500/10', 'border' => 'border-orange-500/20', 'text' => 'text-orange-400', 'ring' => '#fb923c', 'label' => 'SUSPICIOUS RESULT', 'threat' => 'MEDIUM THREAT', 'threatBg' => 'bg-orange-500/20 text-orange-300'],
            'clean' => ['bg' => 'bg-emerald-500/10', 'border' => 'border-emerald-500/20', 'text' => 'text-emerald-400', 'ring' => '#34d399', 'label' => 'APPEARS SAFE', 'threat' => 'LOW THREAT', 'threatBg' => 'bg-emerald-500/20 text-emerald-300'],
            'review' => ['bg' => 'bg-sky-500/10', 'border' => 'border-sky-500/20', 'text' => 'text-sky-400', 'ring' => '#38bdf8', 'label' => 'NEEDS MANUAL REVIEW', 'threat' => 'UNVERIFIED', 'threatBg' => 'bg-sky-500/20 text-sky-300'],
        ];
        $style = $verdictStyles[$analysis->verdict] ?? $verdictStyles['clean'];

        $severityLabel = match (true) {
            $analysis->verdict === 'phishing' && $analysis->risk_score >= 80 => 'CRITICAL',
            $analysis->verdict === 'phishing' => 'HIGH',
            $analysis->verdict === 'suspicious' => 'MEDIUM',
            $analysis->verdict === 'review' => 'UNVERIFIED',
            default => 'LOW',
        };

        $statusColors = [
            'SAFE' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
            'SUSPICIOUS' => 'bg-orange-500/10 text-orange-400 border-orange-500/20',
            'HIGH RISK' => 'bg-red-500/10 text-red-400 border-red-500/20',
            'DETECTED' => 'bg-red-500/10 text-red-400 border-red-500/20',
            'REVIEW' => 'bg-sky-500/10 text-sky-400 border-sky-500/20',
        ];

        $typeIcons = [
            'url' => 'M21 12a9 9 0 11-18 0 9 9 0 0118 0zM3.6 9h16.8M3.6 15h16.8M12 3a13.5 13.5 0 010 18M12 3a13.5 13.5 0 000 18',
            'email' => 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75',
            'phone' => 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
            'screenshot' => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M18 22.5H6a2.25 2.25 0 01-2.25-2.25V3.75A2.25 2.25 0 016 1.5h12a2.25 2.25 0 012.25 2.25v16.5A2.25 2.25 0 0118 22.5zM10.5 8.25a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z',
        ];

        $arcRadius = 80;
        $arcLength = M_PI * $arcRadius;
        $arcOffset = $arcLength - (min($analysis->risk_score, 100) / 100) * $arcLength;
    }
@endphp

@php
    $content = $hasAnalysis
        ? View::make('scan._result-content', [
            'report' => $report,
            'analysis' => $analysis,
            'ctiLookup' => $ctiLookup,
            'style' => $style,
            'severityLabel' => $severityLabel,
            'statusColors' => $statusColors,
            'typeIcons' => $typeIcons,
            'arcLength' => $arcLength,
            'arcOffset' => $arcOffset,
        ])->render()
        : View::make('scan._result-failed', [
            'report' => $report,
        ])->render();
@endphp

@auth
    <x-layouts.dashboard>
        <p class="flex items-center gap-1.5 text-sm text-slate-500 mb-1">
            <a href="{{ route('scan.index') }}" class="hover:text-slate-300">Scan</a>
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            <span class="text-slate-300">Scan Result</span>
        </p>
        <h1 class="text-2xl font-bold text-white mb-1">Scan Result Details</h1>
        <p class="text-slate-400 text-sm mb-6">
            @switch($report->type)
                @case('email') Detailed security analysis of the reported sender email. @break
                @case('phone') Detailed security analysis of the reported phone number. @break
                @case('screenshot') Detailed security analysis of the uploaded screenshot. @break
                @default Detailed security analysis of the submitted website.
            @endswitch
        </p>
        {!! $content !!}
    </x-layouts.dashboard>
@else
    <x-layouts.public>
        {!! $content !!}
    </x-layouts.public>
@endauth