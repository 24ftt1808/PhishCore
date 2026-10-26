@php
    // long URLs and addresses are wrapped by the break-word rules in the stylesheet below
    $wrap = fn ($text) => e((string) $text);
    // Manrope covers Latin text; anything else (e.g. look-alike letters in a domain) is shown in DejaVu so no character is dropped
    $face = fn ($text) => preg_match('/[^\x{0020}-\x{024F}\x{2000}-\x{206F}]/u', (string) $text) ? "font-family: 'DejaVu Sans', sans-serif;" : '';
    $rgb = $verdict['rgb'];
    $hex = $verdict['hex'];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>PhishCore case report {{ $ref }}</title>
<style>
    @page { margin: 32pt 36pt 54pt 36pt; }
    * { box-sizing: border-box; }
    html { background-color: #050b1c; }
    body { font-family: 'Manrope', 'DejaVu Sans', sans-serif; font-size: 8.6pt; color: #cbd5e1; line-height: 1.38; margin: 0; }
    table { border-collapse: collapse; }
    td, th, div, p { word-wrap: break-word; }
    p { margin: 0; }
    .mono { font-family: 'DejaVu Sans Mono', monospace; }
    .white { color: #ffffff; }
    .muted { color: #94a3b8; }
    .label { font-size: 6.6pt; letter-spacing: 1.1pt; color: #94a3b8; text-transform: uppercase; }

    /* page background: the website's navy with soft blue glows, repeated on every page behind the content */
    .page-bg { position: fixed; left: -36pt; top: -32pt; width: 595.28pt; height: 841.89pt; z-index: -1; }

    /* panels: the website's r-card and r-well */
    .card { background-color: rgba(20, 34, 66, .66); border: 1pt solid rgba(148, 163, 184, .2); border-radius: 11pt; padding: 14pt 16pt; margin-top: 10pt; }
    .well { background-color: rgba(8, 15, 32, .55); border: 1pt solid rgba(148, 163, 184, .17); border-radius: 8pt; }
    .sec { page-break-inside: avoid; }
    h2 { font-size: 11.5pt; margin: 0; color: #ffffff; font-weight: bold; line-height: 1.3; }
    .sub { font-size: 8.2pt; color: #cbd5e1; margin-top: 2pt; line-height: 1.4; }
    .tilet { border-collapse: separate; }
    .tile { width: 28pt; height: 28pt; padding: 0; border-radius: 8pt; text-align: center; vertical-align: middle; }
    .tile img { display: block; margin: 0 auto; }
    .pill { font-size: 6.6pt; font-weight: bold; padding: 2.2pt 6.5pt; border-radius: 8pt; letter-spacing: .2pt; white-space: nowrap; }

    /* header */
    .top { width: 100%; padding-bottom: 8pt; }
    .brand { font-size: 15pt; font-weight: bold; color: #ffffff; letter-spacing: -.3pt; line-height: 1.1; }
    .tag { font-size: 6.3pt; letter-spacing: 1.9pt; margin-top: 2pt; color: #7dd3fc; font-weight: bold; }
    .doc { font-size: 6.3pt; letter-spacing: 1.7pt; color: #7dd3fc; font-weight: bold; text-align: right; }
    .refn { font-size: 9pt; color: #ffffff; text-align: right; font-weight: bold; margin-top: 1pt; }

    /* verdict card */
    .hero { padding: 0; margin-top: 4pt; border-radius: 13pt; }
    .hero-t { width: 100%; table-layout: fixed; }
    .hero-t td { vertical-align: top; }
    .htile { width: 36pt; height: 36pt; padding: 0; border-radius: 10pt; text-align: center; vertical-align: middle; }
    .htile img { display: block; margin: 0 auto; }
    .headline { font-family: 'DejaVu Sans Mono', monospace; font-size: 17pt; font-weight: bold; line-height: 1.15; letter-spacing: -.6pt; }
    .meta { margin-top: 3pt; font-size: 7.4pt; color: #b0bccf; line-height: 1.4; }
    .reason { margin-top: 11pt; font-size: 9.4pt; color: #e2e8f0; line-height: 1.42; }
    .ainote { margin-top: 9pt; padding: 6pt 10pt; line-height: 1.4; font-size: 7.4pt; color: #cbd5e1; background-color: rgba(148, 163, 184, .1); border: 1pt solid rgba(148, 163, 184, .25); border-radius: 8pt; }
    .target { margin-top: 4pt; padding: 7pt 10pt; font-size: 7.6pt; line-height: 1.4; color: #ffffff; }
    .score { font-size: 30pt; font-weight: bold; color: #ffffff; line-height: 1; text-align: center; margin-top: -34pt; }
    .score-of { font-size: 6.6pt; letter-spacing: 1.2pt; color: #cbd5e1; text-align: center; margin-top: 13pt; }
    .score-hint { font-size: 6.6pt; color: #94a3b8; text-align: center; margin-top: 3pt; }
    .sev { font-size: 9pt; font-weight: bold; text-align: center; margin-top: 3pt; letter-spacing: 1pt; }
    .facts-wrap { border-top: 1pt solid rgba(255, 255, 255, .1); background-color: rgba(0, 0, 0, .1); border-radius: 0 0 13pt 13pt; }
    .facts { width: 100%; table-layout: fixed; }
    .facts .label { font-size: 6.1pt; letter-spacing: .7pt; white-space: nowrap; }
    .facts td { padding: 9pt 15pt; vertical-align: top; border-left: 1pt solid rgba(255, 255, 255, .1); }
    .facts td:first-child { border-left: 0; }
    .facts .v { margin-top: 2pt; font-size: 10pt; font-weight: bold; }

    /* VirusTotal */
    .vt { width: 100%; table-layout: fixed; border-collapse: separate; border-spacing: 0; }
    .vt td { padding: 0 3pt; vertical-align: top; }
    .vt td:first-child { padding-left: 0; }
    .vt td:last-child { padding-right: 0; }
    .vt .cell { padding: 9pt 6pt 8pt 6pt; text-align: center; }
    .vt .n { font-size: 16pt; font-weight: bold; line-height: 1.2; }
    .vt .l { font-size: 7.2pt; color: #cbd5e1; margin-top: 1pt; }

    /* what we checked */
    .sh { margin-top: 18pt; }
    .gbar { margin-top: 12pt; padding: 7pt 10pt; background-color: rgba(8, 15, 32, .5); border-radius: 9pt; page-break-after: avoid; }
    .gbar table { width: 100%; }
    .gcount { width: 19pt; height: 19pt; padding: 0; border-radius: 10pt; text-align: center; vertical-align: middle; font-size: 8pt; font-weight: bold; }
    .gtitle { font-size: 9.4pt; font-weight: bold; color: #f1f5f9; line-height: 1.25; }
    .gpeek { font-size: 7pt; color: #94a3b8; line-height: 1.3; margin-top: 1pt; }
    .gnote { font-size: 7.4pt; color: #94a3b8; margin-top: 7pt; }
    .grid { width: 100%; table-layout: fixed; border-collapse: separate; border-spacing: 0; margin-top: 7pt; page-break-inside: avoid; }
    .ck { width: 49.2%; vertical-align: top; padding: 11pt 12pt 10pt 12pt; background-color: rgba(20, 34, 66, .66); border: 1pt solid rgba(148, 163, 184, .2); border-radius: 9pt; }
    .gap { width: 1.6%; }
    .ck-title { font-size: 9.2pt; font-weight: bold; color: #ffffff; line-height: 1.3; }
    .ck-head { margin-top: 6pt; font-size: 9.8pt; font-weight: bold; color: #ffffff; line-height: 1.3; }
    .ck-fact { margin-top: 2.5pt; font-size: 7.6pt; color: #e2e8f0; line-height: 1.4; }
    .ck-fact .lab { color: #94a3b8; }
    .ck-lbl { color: #94a3b8; font-weight: bold; }
    .ck-msg { margin-top: 6pt; font-size: 8.1pt; color: #e2e8f0; line-height: 1.42; }
    .ck-mean { margin-top: 6pt; font-size: 7pt; color: #94a3b8; line-height: 1.42; }
    .ck-mean b { color: #7dd3fc; font-weight: normal; }
    .ck-tech { margin-top: 6pt; font-size: 6.6pt; color: #64748b; }

    /* why this score */
    .brow { margin-top: 8pt; page-break-inside: avoid; }
    .brow table { width: 100%; }
    .track { margin-top: 4pt; height: 5pt; border-radius: 3pt; background-color: rgba(51, 65, 85, .5); }
    .fill { height: 5pt; border-radius: 3pt; }

    /* what you should do */
    .steps { width: 100%; margin-top: 9pt; }
    .steps td { vertical-align: top; padding: 0 0 6pt 0; }
    .num { width: 16pt; height: 16pt; padding: 0; border-radius: 8pt; background-color: rgba(251, 146, 60, .15); border: 1pt solid rgba(251, 146, 60, .35); color: #fdba74; font-size: 7.4pt; font-weight: bold; text-align: center; vertical-align: middle; }
    .box { padding: 10pt 12pt; font-size: 8.3pt; color: #e2e8f0; }

    /* technical + investigation */
    .kv { width: 100%; table-layout: fixed; margin-top: 10pt; }
    .kv td { padding: 0 10pt 7pt 0; vertical-align: top; }
    .kv .v { margin-top: 2pt; font-size: 8.6pt; color: #f1f5f9; }
    .chain { font-size: 7.6pt; margin: 2.5pt 0; color: #e2e8f0; }
    .rawl { font-size: 6.8pt; margin: 3pt 0; color: #cbd5e1; line-height: 1.5; }
    .tl { width: 100%; }
    .tl td { padding: 6pt 0; border-bottom: 1pt solid rgba(148, 163, 184, .12); vertical-align: top; }
    .note { margin-top: 12pt; padding: 0 4pt; font-size: 7pt; color: #7c8ba1; line-height: 1.5; }

    .footer { position: fixed; bottom: -32pt; left: 0; right: 0; font-size: 6.8pt; color: #64748b; border-top: 1pt solid rgba(148, 163, 184, .16); padding-top: 6pt; }
    .footer .pn:before { content: counter(page); }
</style>
</head>
<body>

@if ($bg)
    <img class="page-bg" src="{{ $bg }}" alt="">
@endif

<div class="footer">
    <table width="100%"><tr>
        <td>PhishCore Detection Platform &middot; {{ $ref }}</td>
        <td style="text-align: right;">Generated {{ $generatedAt }} &middot; Page <span class="pn"></span></td>
    </tr></table>
</div>

{{-- HEADER --}}
<table class="top">
    <tr>
        @if ($logo)
            <td style="width: 46pt;"><img src="{{ $logo }}" width="38" height="38" alt=""></td>
        @endif
        <td style="vertical-align: middle;">
            <div class="brand">PhishCore</div>
            <div class="tag">DETECTION PLATFORM</div>
        </td>
        <td style="vertical-align: middle;">
            <div class="doc">SCAN CASE REPORT</div>
            <div class="refn mono">{{ $ref }}</div>
        </td>
    </tr>
</table>

{{-- VERDICT --}}
<div class="card hero" style="border: 1pt solid rgba({{ $rgb }}, .5); @if ($heroGlow) background-image: url('{{ $heroGlow }}'); background-size: 100% 100%; @endif">
    <table class="hero-t">
        <tr>
            <td style="padding: 17pt 16pt 16pt 18pt;">
                <table width="100%">
                    <tr>
                        <td style="width: 46pt; vertical-align: top;"><img src="{{ $ic['verdict'] }}" width="36" height="36" alt=""></td>
                        <td style="vertical-align: top;">
                            <div class="headline" style="color: {{ $hex }};">{{ $verdict['headline'] }}</div>
                            <div class="meta"><span class="mono">{{ $ref }}</span> &middot; Scanned {{ $scannedAt }}@if ($scannedBy) &middot; by {{ $scannedBy }}@endif</div>
                        </td>
                    </tr>
                </table>
                @if (! empty($verdict['caveat']))
                    <div class="reason">{{ $verdict['caveat'] }}</div>
                @endif
                @if ($topReason)
                    <div class="reason" style="{{ $face($topReason) }}">{!! $wrap($topReason) !!}</div>
                @endif
                @if ($aiSkipped)
                    <div class="ainote">The AI review could not run this time, so this result uses PhishCore's built-in rules only. Scanning again in a few minutes may add the AI check.</div>
                @endif
                <div class="label" style="margin-top: 14pt;">{{ $targetLabel }}</div>
                <div class="well target">
                    <img src="{{ $ic['target'] }}" width="10" height="10" alt="" style="float: left; margin-top: 3.8pt;">
                    <div class="mono" style="margin-left: 16pt; {{ $face($target) }}">{!! $wrap($target) !!}</div>
                </div>
            </td>
            <td style="width: 178pt; border-left: 1pt solid rgba(255, 255, 255, .1); padding: 26pt 8pt 18pt 8pt; vertical-align: middle;">
                <div style="text-align: center;"><img src="{{ $gauge }}" width="150" height="84" alt=""></div>
                <div class="score">{{ $score }}</div>
                <div class="score-of">RISK SCORE / 100</div>
                <div class="score-hint">0 = very likely safe, 100 = very dangerous</div>
                <div class="sev" style="color: {{ $hex }};">{{ $severity }}</div>
            </td>
        </tr>
    </table>
    <div class="facts-wrap">
        <table class="facts">
            <tr>
                @foreach ($facts as $fact)
                    <td>
                        <div class="label">{{ $fact[0] }}</div>
                        <div class="v" style="color: {{ $fact[2] }};">{{ $fact[1] }}</div>
                    </td>
                @endforeach
            </tr>
        </table>
    </div>
</div>

{{-- VIRUSTOTAL --}}
@if ($vt)
    <div class="card sec">
        <table width="100%">
            <tr>
                <td style="width: 38pt; vertical-align: top;"><img src="{{ $ic['vt'] }}" width="28" height="28" alt=""></td>
                <td style="vertical-align: top;">
                    <h2>{{ $vt['source'] }} / CTI Lookup</h2>
                    <div class="sub">Cross-referenced against {{ $vt['total'] }} independent security vendors</div>
                </td>
                <td style="width: 84pt; text-align: right; vertical-align: top; padding-top: 2pt;">
                    @if ($vt['flagged'] > 0)
                        <span class="pill" style="color: #fca5a5; background-color: rgba(248, 113, 113, .1); border: 1pt solid rgba(248, 113, 113, .3);">{{ $vt['flagged'] }} / {{ $vt['total'] }} FLAGGED</span>
                    @else
                        <span class="pill" style="color: #6ee7b7; background-color: rgba(52, 211, 153, .1); border: 1pt solid rgba(52, 211, 153, .3);">{{ $vt['flagged'] }} / {{ $vt['total'] }} FLAGGED</span>
                    @endif
                </td>
            </tr>
        </table>
        <table class="vt" style="margin-top: 12pt;">
            <tr>
                <td><div class="well cell"><div class="n" style="color: {{ $vt['flagged'] > 0 ? '#f87171' : '#34d399' }};">{{ $vt['malicious'] }}</div><div class="l">Malicious</div></div></td>
                <td><div class="well cell"><div class="n" style="color: #fb923c;">{{ $vt['suspicious'] }}</div><div class="l">Suspicious</div></div></td>
                <td><div class="well cell"><div class="n" style="color: #34d399;">{{ $vt['harmless'] }}</div><div class="l">Harmless</div></div></td>
                <td><div class="well cell"><div class="n" style="color: #cbd5e1;">{{ $vt['undetected'] }}</div><div class="l">Undetected</div></div></td>
            </tr>
        </table>
        <div class="muted" style="font-size: 7pt; margin-top: 10pt;">
            @if ($vt['threat'] !== null)Threat score: {{ $vt['threat'] }}% &middot; @endif
            Looked up @if ($vt['when']){{ $vt['when'] }} @endif
            via VirusTotal's public API (70+ integrated antivirus and security engines).
        </div>
    </div>
@endif

<div class="note">
    This report was produced by automated analysis. Risk scores and verdicts are indicators to support a
    decision, not proof of intent, and a result can change as a site is updated or taken down. Review the
    findings above before taking action.
</div>

{{-- WHAT WE CHECKED --}}
@foreach ($groups as $group)
    @foreach (array_chunk($group['checks'], 2) as $rowIndex => $row)
        {{-- the heading, the group bar and the first row of cards always stay together on one page --}}
        @if ($rowIndex === 0)
            <div class="sec">
                @if ($loop->parent->first)
                    <h2 class="sh">What we checked</h2>
                    <div class="sub">{{ $summary }}</div>
                @endif
                <div class="gbar" style="border: 1pt solid rgba({{ $group['rgb'] }}, .35);">
                    <table>
                        <tr>
                            <td style="width: 28pt; vertical-align: middle;"><table style="width: 19pt; border-collapse: separate;"><tr><td class="gcount" style="color: {{ $group['hex'] }}; background-color: rgba({{ $group['rgb'] }}, .16); border: 1pt solid rgba({{ $group['rgb'] }}, .4);">{{ $group['count'] }}</td></tr></table></td>
                            <td style="vertical-align: middle;">
                                <div class="gtitle">{{ $group['title'] }}</div>
                                <div class="gpeek">{{ $group['peek'] }}</div>
                            </td>
                        </tr>
                    </table>
                </div>
                @if ($group['note'])
                    <div class="gnote">{{ $group['note'] }}</div>
                @endif
        @endif

        <table class="grid">
            <tr>
                @foreach ($row as $check)
                    <td class="ck">
                        <table width="100%">
                            <tr>
                                <td style="width: 16pt; vertical-align: middle;"><img src="{{ $check['icon'] }}" width="11" height="11" alt="" style="display: block;"></td>
                                <td class="ck-title" style="vertical-align: middle;">{{ $check['title'] }}</td>
                                <td style="vertical-align: middle; text-align: right; width: 62pt;"><span class="pill" style="color: {{ $check['text'] }}; background-color: rgba({{ $check['rgb'] }}, .1); border: 1pt solid rgba({{ $check['rgb'] }}, .3);">{{ $check['label'] }}</span></td>
                            </tr>
                        </table>
                        @if ($group['key'] === 'warn')
                            @if ($check['headline'])
                                <div class="ck-head" style="{{ $face($check['headline']) }}">{!! $wrap($check['headline']) !!}</div>
                            @endif
                            @if ($check['body'] !== '')
                                <div class="ck-msg" style="{{ $check['headline'] ? 'margin-top: 3pt; ' : '' }}{{ $face($check['body']) }}">{!! $wrap($check['body']) !!}</div>
                            @endif
                            @foreach ($check['facts'] as $fact)
                                <div class="ck-fact" style="{{ $face($fact['value']) }}"><span class="lab">{{ $fact['label'] }}:</span> {!! $wrap($fact['value']) !!}</div>
                            @endforeach
                        @else
                            @if ($check['looked'])
                                <div class="ck-msg"><span class="ck-lbl">Looked at:</span> {{ $check['looked'] }}</div>
                            @endif
                            @if ($check['found'] !== '')
                                <div class="ck-msg" style="margin-top: 3pt; {{ $face($check['found']) }}"><span class="ck-lbl">{{ $group['key'] === 'ok' ? 'Found:' : 'Result:' }}</span> {!! $wrap($check['found']) !!}</div>
                            @endif
                        @endif
                        @if ($group['key'] === 'warn' && $check['meaning'])
                            <div class="ck-mean"><b>What does this check mean?</b> {{ $check['meaning'] }}</div>
                        @endif
                        @if ($check['technical'])
                            <div class="ck-tech">Technical name: {{ $check['technical'] }}</div>
                        @endif
                    </td>
                    @if ($loop->first && count($row) > 1)
                        <td class="gap"></td>
                    @endif
                @endforeach
                @if (count($row) === 1)
                    <td class="gap"></td>
                    <td class="ck-fill" style="width: 49.2%;"></td>
                @endif
            </tr>
        </table>

        @if ($rowIndex === 0)
            </div>
        @endif
    @endforeach
@endforeach

{{-- WHY THIS SCORE --}}
<div class="card sec">
    <h2>Why this score?</h2>
    <div class="sub">The score goes from 0 (very likely safe) to 100 (very dangerous). These are the checks that pushed it up, biggest first.</div>
    <div class="muted" style="font-size: 7.2pt; margin-top: 2pt;">The percentage is how much of the score each check is responsible for.</div>
    @if ($hasScore)
        @foreach ($breakdown as $row)
            <div class="brow">
                <table>
                    <tr>
                        <td style="color: #f1f5f9; font-size: 8.4pt;">{{ $row['title'] }}</td>
                        <td style="text-align: right; width: 40pt; font-weight: bold; font-size: 8.4pt; color: {{ $row['text'] }};">{{ $row['pct'] }}%</td>
                    </tr>
                </table>
                <div class="track"><div class="fill" style="width: {{ $row['width'] }}%; background-color: {{ $row['color'] }};"></div></div>
            </div>
        @endforeach
    @elseif ($verdict['icon'] === 'info')
        <div class="sub" style="margin-top: 10pt;">No automatic score was generated for this report &mdash; it needs a human to review the extracted content.</div>
    @else
        <div class="sub" style="margin-top: 10pt;">Nothing pushed the score up &mdash; this report passed every check cleanly.</div>
    @endif
</div>

{{-- WHAT YOU SHOULD DO --}}
@if ($advice)
    <div class="card sec">
        <table width="100%">
            <tr>
                <td style="width: 38pt; vertical-align: top;"><img src="{{ $ic['advice'] }}" width="28" height="28" alt=""></td>
                <td style="vertical-align: top;">
                    <h2>What you should do</h2>
                    <div class="sub">{{ $advice['intro'] }}</div>
                </td>
            </tr>
        </table>
        <table class="steps">
            @foreach ($advice['steps'] as $i => $step)
                <tr>
                    <td style="width: 26pt;"><table style="width: 16pt; border-collapse: separate;"><tr><td class="num">{{ $i + 1 }}</td></tr></table></td>
                    <td style="color: #f1f5f9; padding-top: 2pt;">{{ $step }}</td>
                </tr>
            @endforeach
        </table>
        <div class="well box" style="margin-top: 4pt; page-break-inside: avoid;">
            <div class="white" style="font-weight: bold; margin-bottom: 5pt;">{{ $advice['affectedTitle'] }}</div>
            @foreach ($advice['affected'] as $i => $line)
                <div style="margin: 3pt 0;">{{ $i + 1 }}. {{ $line }}</div>
            @endforeach
        </div>
    </div>
@endif

{{-- INVESTIGATION --}}
@if ($investigation)
    <div class="card sec">
        <h2>Investigation</h2>
        <table class="kv">
            <tr>
                <td>
                    <div class="label">Status</div>
                    <div class="v" style="margin-top: 4pt;"><span class="pill" style="color: {{ $investigation['text'] }}; background-color: rgba({{ $investigation['rgb'] }}, .12); border: 1pt solid rgba({{ $investigation['rgb'] }}, .32);">{{ strtoupper($investigation['status']) }}</span></div>
                </td>
                @if ($investigation['assignee'])
                    <td>
                        <div class="label">Assigned to</div>
                        <div class="v">{{ $investigation['assignee'] }}</div>
                    </td>
                @endif
                @if ($investigation['resolved'])
                    <td>
                        <div class="label">Resolved</div>
                        <div class="v">{{ $investigation['resolved'] }}</div>
                    </td>
                @endif
            </tr>
        </table>
        @if ($investigation['notes'])
            <div class="label" style="margin: 4pt 0 4pt 0;">Team notes</div>
            <div class="well box">{{ $investigation['notes'] }}</div>
        @endif
        @if (count($investigation['timeline']) > 0)
            <div class="label" style="margin: 12pt 0 2pt 0;">Status timeline</div>
            <table class="tl">
                @foreach ($investigation['timeline'] as $event)
                    <tr>
                        <td style="width: 34%; font-weight: bold; color: {{ $event['text'] }};">{{ $event['label'] }}</td>
                        <td class="muted">{{ $event['when'] }}@if ($event['by']) &middot; {{ $event['by'] }}@endif</td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>
@endif

{{-- TECHNICAL INFORMATION --}}
<div class="card sec">
    <table width="100%">
        <tr>
            <td style="width: 18pt; vertical-align: middle;"><img src="{{ $ic['tech'] }}" width="11" height="11" alt="" style="display: block;"></td>
            <td style="vertical-align: middle;"><h2 style="font-size: 10.5pt;">Technical information</h2></td>
        </tr>
    </table>
    <div style="border-top: 1pt solid rgba(148, 163, 184, .2); margin-top: 9pt;"></div>
    <table class="kv">
        @foreach (array_chunk($technical, 2) as $pair)
            <tr>
                @foreach ($pair as $row)
                    <td style="width: 50%;">
                        <div class="label">{{ $row[0] }}</div>
                        <div class="v {{ in_array($row[0], ['Scan reference ID', 'IP address'], true) ? 'mono' : '' }}" style="{{ $face($row[1]) }}">{!! $wrap($row[1]) !!}</div>
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>
    @if (count($chain) > 0)
        <div class="label" style="margin: 4pt 0 4pt 0;">Redirect chain</div>
        <div class="well box" style="padding: 8pt 10pt;">
            @foreach ($chain as $i => $hop)
                <div class="chain mono">{{ $i + 1 }}. {!! $wrap($hop) !!}@if ($i === count($chain) - 1) <span style="color: #fdba74;">(final)</span>@endif</div>
            @endforeach
        </div>
    @endif
</div>

@if (count($raw) > 0)
    <div class="card sec">
        <h2 style="font-size: 10.5pt;">Raw check results</h2>
        <div class="sub muted" style="font-size: 7.2pt;">The scanner's original wording for each check, kept for reference and for investigators.</div>
        <div class="well box" style="padding: 7pt 10pt; margin-top: 8pt;">
            @foreach ($raw as $check)
                <div class="rawl mono" style="{{ $face($check['raw']) }}"><span class="white">{{ $check['name'] }}:</span> {{ $check['status'] }} ({{ $check['points'] }} pts) &mdash; {!! $wrap($check['raw']) !!}</div>
            @endforeach
        </div>
    </div>
@endif



</body>
</html>