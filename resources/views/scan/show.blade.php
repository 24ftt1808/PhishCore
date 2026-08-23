@php
    $verdictStyles = [
        'phishing' => ['bg' => 'bg-red-500/10', 'border' => 'border-red-500/20', 'text' => 'text-red-400', 'ring' => 'text-red-500', 'label' => 'PHISHING WEBSITE DETECTED', 'threat' => 'HIGH THREAT', 'threatBg' => 'bg-red-500/20 text-red-300'],
        'suspicious' => ['bg' => 'bg-orange-500/10', 'border' => 'border-orange-500/20', 'text' => 'text-orange-400', 'ring' => 'text-orange-500', 'label' => 'SUSPICIOUS WEBSITE', 'threat' => 'MEDIUM THREAT', 'threatBg' => 'bg-orange-500/20 text-orange-300'],
        'clean' => ['bg' => 'bg-emerald-500/10', 'border' => 'border-emerald-500/20', 'text' => 'text-emerald-400', 'ring' => 'text-emerald-500', 'label' => 'WEBSITE APPEARS SAFE', 'threat' => 'LOW THREAT', 'threatBg' => 'bg-emerald-500/20 text-emerald-300'],
    ];
    $style = $verdictStyles[$analysis->verdict] ?? $verdictStyles['clean'];

    $statusColors = [
        'SAFE' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        'SUSPICIOUS' => 'bg-orange-500/10 text-orange-400 border-orange-500/20',
        'HIGH RISK' => 'bg-red-500/10 text-red-400 border-red-500/20',
        'DETECTED' => 'bg-red-500/10 text-red-400 border-red-500/20',
    ];

    $checkIcons = [
        'SSL Certificate' => '🔒',
        'Domain Age' => '📅',
        'URL Structure' => '🔍',
        'Blacklist Database' => '🛡️',
    ];

    $radius = 54;
    $circumference = 2 * M_PI * $radius;
    $offset = $circumference - ($analysis->risk_score / 100) * $circumference;
@endphp

@php
    $content = View::make('scan._result-content', [
        'report' => $report,
        'analysis' => $analysis,
        'style' => $style,
        'statusColors' => $statusColors,
        'checkIcons' => $checkIcons,
        'circumference' => $circumference,
        'offset' => $offset,
    ])->render();
@endphp

@auth
    <x-layouts.dashboard>
        <p class="text-sm text-slate-500 mb-1">
            <a href="{{ route('scan.index') }}" class="hover:text-slate-300">Scan Website</a> <span class="mx-1">›</span> Scan Result
        </p>
        <h1 class="text-2xl font-bold text-white mb-6">Scan Result Details</h1>
        {!! $content !!}
    </x-layouts.dashboard>
@else
    <x-layouts.public>
        {!! $content !!}
    </x-layouts.public>
@endauth