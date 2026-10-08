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
@php
    [$hr, $hg, $hb] = array_map('intval', explode(',', $rgb));
    $heroBg = sprintf('rgb(%d, %d, %d)', (int) round(11 + ($hr - 11) * .1), (int) round(18 + ($hg - 18) * .1), (int) round(36 + ($hb - 36) * .1));
@endphp
<style>
    @page { margin: 32pt 36pt 54pt 36pt; }
    * { box-sizing: border-box; }
    html { background-color: #050b1c; }
    body { font-family: 'Manrope', 'DejaVu Sans', sans-serif; font-size: 8.8pt; color: #cbd5e1; line-height: 1.5; margin: 0; }
    table { border-collapse: collapse; }
    td, th, div { word-wrap: break-word; }
    .mono { font-family: 'DejaVu Sans Mono', monospace; }
    .white { color: #ffffff; }
    .muted { color: #94a3b8; }
    .label { font-size: 6.8pt; letter-spacing: 1.2pt; color: #cbd5e1; text-transform: uppercase; font-weight: normal; }

    /* page background: the website's navy with soft blue glows, repeated on every page behind the content */
    .page-bg { position: fixed; left: -36pt; top: -32pt; width: 595.28pt; height: 841.89pt; z-index: -1; }

    .card { background-color: rgba(24, 40, 76, .62); border: 1pt solid rgba(148, 163, 184, .22); border-radius: 10pt; padding: 14pt 16pt; margin-top: 12pt; }
    .well { background-color: rgba(8, 15, 32, .55); border: 1pt solid rgba(148, 163, 184, .18); border-radius: 7pt; }
    .sec { page-break-inside: avoid; }
    h2 { font-size: 10.5pt; margin: 0 0 9pt 0; color: #ffffff; font-weight: bold; }
    .hr td { border-bottom: 1pt solid rgba(148, 163, 184, .16); }

    .top { width: 100%; padding-bottom: 10pt; }
    .brand { font-size: 15pt; font-weight: bold; color: #ffffff; letter-spacing: -.3pt; line-height: 1.1; }
    .sub { font-size: 6.4pt; letter-spacing: 1.9pt; margin-top: 2pt; color: #7dd3fc; font-weight: bold; }
    .doc { font-size: 6.3pt; letter-spacing: 1.7pt; color: #7dd3fc; font-weight: bold; text-align: right; }
    .ref { font-size: 9pt; color: #ffffff; text-align: right; font-weight: bold; margin-top: 1pt; }

    .hero { background-color: {{ $heroBg }}; border: 1pt solid rgba({{ $rgb }}, .34); border-radius: 12pt; padding: 0; margin-top: 4pt; }
    .hero-t { width: 100%; table-layout: fixed; }
    .hero-t td { vertical-align: top; }
    .tile { width: 36pt; height: 36pt; background-color: rgba({{ $rgb }}, .12); border: 1pt solid rgba({{ $rgb }}, .34); border-radius: 10pt; padding: 8pt 0 0 0; text-align: center; line-height: 1; }
    .tile img { vertical-align: top; }
    .headline { font-family: 'DejaVu Sans Mono', monospace; font-size: 16.5pt; font-weight: bold; color: {{ $hex }}; line-height: 1.15; letter-spacing: -.6pt; }
    .meta { margin-top: 3pt; font-size: 7.4pt; color: #cbd5e1; line-height: 1.4; }
    .reason { margin-top: 14pt; font-size: 10.5pt; color: #f1f5f9; line-height: 1.5; }
    .target { margin-top: 5pt; padding: 8pt 10pt; font-size: 7.7pt; color: #ffffff; background-color: rgba(4, 9, 20, .6); border-radius: 8pt; }
    .score { font-size: 28pt; font-weight: bold; color: #ffffff; line-height: 1; text-align: center; margin-top: -32pt; }
    .score-of { font-size: 6.5pt; letter-spacing: 1.2pt; color: #cbd5e1; text-align: center; margin-top: 12pt; font-weight: normal; }
    .sev { font-size: 9pt; font-weight: bold; color: {{ $hex }}; text-align: center; margin-top: 4pt; letter-spacing: 1pt; }
    .facts-wrap { border-top: 1pt solid rgba(255, 255, 255, .1); }
    .facts { width: 100%; table-layout: fixed; }
    .facts td { padding: 11pt 18pt; vertical-align: top; border-left: 1pt solid rgba(255, 255, 255, .08); }
    .facts td:first-child { border-left: 0; }
    .facts .v { margin-top: 3pt; font-size: 10.5pt; font-weight: bold; color: #ffffff; }

    .checks { width: 100%; table-layout: fixed; }
    .checks th { text-align: left; padding: 0 6pt 6pt 0; font-size: 6.3pt; letter-spacing: 1.2pt; color: #94a3b8; text-transform: uppercase; border-bottom: 1pt solid rgba(148, 163, 184, .22); }
    .checks td { padding: 7pt 6pt 7pt 0; vertical-align: top; border-bottom: 1pt solid rgba(148, 163, 184, .12); }
    .checks tr { page-break-inside: avoid; }
    .pill { font-size: 6.2pt; font-weight: bold; padding: 2pt 6pt; letter-spacing: .7pt; border-radius: 8pt; }

    .kv { width: 100%; table-layout: fixed; }
    .kv td { padding: 0 10pt 0 0; vertical-align: top; }
    .kv .v { margin-top: 2pt; font-size: 9pt; color: #ffffff; }
    .vt { width: 100%; table-layout: fixed; }
    .vt td { padding: 0 3pt; }
    .vt td:first-child { padding-left: 0; }
    .vt td:last-child { padding-right: 0; }
    .vt .cell { padding: 9pt 6pt; text-align: center; }
    .vt .n { font-size: 16pt; font-weight: bold; line-height: 1.2; }
    .chain { font-size: 7.8pt; margin: 2pt 0; color: #e2e8f0; }
    .box { padding: 8pt 10pt; font-size: 8.5pt; color: #e2e8f0; }
    .tl { width: 100%; }
    .tl td { padding: 6pt 0; border-bottom: 1pt solid rgba(148, 163, 184, .12); vertical-align: top; }
    .note { margin-top: 14pt; font-size: 7pt; color: #64748b; line-height: 1.6; }

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

<table class="top">
    <tr>
        @if ($logo)
            <td style="width: 46pt;"><img src="{{ $logo }}" width="38" height="38" alt=""></td>
        @endif
        <td style="vertical-align: middle;">
            <div class="brand">PhishCore</div>
            <div class="sub">DETECTION PLATFORM</div>
        </td>
        <td style="vertical-align: middle;">
            <div class="doc">SCAN CASE REPORT</div>
            <div class="ref mono">{{ $ref }}</div>
        </td>
    </tr>
</table>

<div class="card hero">
    <table class="hero-t">
        <tr>
            <td style="padding: 20pt 18pt 18pt 20pt;">
                <table width="100%">
                    <tr>
                        <td style="width: 46pt; vertical-align: top;"><div class="tile"><img src="{{ $icon }}" width="19" height="19" alt=""></div></td>
                        <td style="vertical-align: top;">
                            <div class="headline">{{ $verdict['headline'] }}</div>
                            <div class="meta">{{ $ref }} &middot; Scanned {{ $scannedAt }}@if ($scannedBy) &middot; by {{ $scannedBy }}@endif</div>
                        </td>
                    </tr>
                </table>
                @if (! empty($verdict['caveat']))
                    <div class="reason">{{ $verdict['caveat'] }}</div>
                @endif
                @if ($topReason)
                    <div class="reason">{{ $topReason }}</div>
                @endif
                <div class="label" style="margin-top: 14pt;">{{ $targetLabel }}</div>
                <div class="target">
                    <img src="{{ $globe }}" width="10" height="10" alt="" style="float: left; margin-top: 1pt;">
                    <div class="mono" style="margin-left: 16pt; word-wrap: break-word;">{!! $wrap($target) !!}</div>
                </div>
            </td>
            <td style="width: 180pt; border-left: 1pt solid rgba(255, 255, 255, .1); padding: 30pt 8pt 18pt 8pt;">
                <div style="text-align: center;"><img src="{{ $gauge }}" width="150" height="84" alt=""></div>
                <div class="score">{{ $score }}</div>
                <div class="score-of">RISK SCORE / 100</div>
                <div class="sev">{{ $severity }}</div>
            </td>
        </tr>
    </table>
    <div class="facts-wrap">
        <table class="facts">
            <tr>
                @foreach ($facts as $fact)
                    <td>
                        <div class="label">{{ $fact[0] }}</div>
                        <div class="v" @if (! empty($fact[2])) style="color: {{ $hex }};" @endif>{{ $fact[1] }}</div>
                    </td>
                @endforeach
            </tr>
        </table>
    </div>
</div>

<div class="card">
    <h2>Detection details</h2>
    @if (count($checks) > 0)
        <table class="checks">
            <thead>
                <tr>
                    <th style="width: 21%;">Check</th>
                    <th style="width: 16%;">Result</th>
                    <th style="width: 8%;">Pts</th>
                    <th>Finding</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($checks as $check)
                    <tr>
                        <td class="white" style="font-weight: bold;">{{ $check['name'] }}</td>
                        <td><span class="pill" style="color: {{ $check['text'] }}; background-color: rgba({{ $check['rgb'] }}, .14); border: 1pt solid rgba({{ $check['rgb'] }}, .34);">{{ $check['status'] }}</span></td>
                        <td class="mono" style="color: {{ $check['points'] > 0 ? '#fdba74' : '#94a3b8' }};">{{ $check['points'] > 0 ? '+' . $check['points'] : '0' }}</td>
                        <td style="{{ $face($check['message']) }}">{!! $wrap($check['message']) !!}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="muted" style="margin: 0;">No individual check results were recorded for this scan.</p>
    @endif
</div>

@if ($vt)
    <div class="card sec">
        <h2>VirusTotal vendor results</h2>
        <table class="vt">
            <tr>
                <td><div class="well cell"><div class="n" style="color: #f87171;">{{ $vt['malicious'] }}</div><div class="label">Malicious</div></div></td>
                <td><div class="well cell"><div class="n" style="color: #fb923c;">{{ $vt['suspicious'] }}</div><div class="label">Suspicious</div></div></td>
                <td><div class="well cell"><div class="n" style="color: #34d399;">{{ $vt['harmless'] }}</div><div class="label">Harmless</div></div></td>
                <td><div class="well cell"><div class="n" style="color: #94a3b8;">{{ $vt['undetected'] }}</div><div class="label">Undetected</div></div></td>
            </tr>
        </table>
    </div>
@endif

@if ($advice)
    <div class="card sec">
        <h2>What you should do</h2>
        <div class="muted" style="margin-bottom: 8pt;">{{ $advice['intro'] }}</div>
        <table class="tl">
            @foreach ($advice['steps'] as $i => $step)
                <tr>
                    <td class="mono" style="width: 18pt; color: #fdba74; font-weight: bold;">{{ $i + 1 }}</td>
                    <td class="white">{{ $step }}</td>
                </tr>
            @endforeach
        </table>
        <div class="well box" style="margin-top: 10pt;">
            <div class="white" style="font-weight: bold; margin-bottom: 4pt;">{{ $advice['affectedTitle'] }}</div>
            @foreach ($advice['affected'] as $i => $line)
                <div style="margin: 2pt 0;">{{ $i + 1 }}. {{ $line }}</div>
            @endforeach
        </div>
    </div>
@endif

<div class="card sec">
    <h2>Technical information</h2>
    <table class="kv">
        <tr>
            @foreach ($technical as $row)
                <td>
                    <div class="label">{{ $row[0] }}</div>
                    <div class="v" style="{{ $face($row[1]) }}">{!! $wrap($row[1]) !!}</div>
                </td>
            @endforeach
        </tr>
    </table>
    @if (count($chain) > 0)
        <div class="label" style="margin: 11pt 0 4pt 0;">Redirect chain</div>
        <div class="well box">
            @foreach ($chain as $i => $hop)
                <div class="chain mono">{{ $i + 1 }}. {!! $wrap($hop) !!}@if ($i === count($chain) - 1) <span style="color: #fdba74;">(final)</span>@endif</div>
            @endforeach
        </div>
    @endif
</div>

@if ($investigation)
    <div class="card sec">
        <h2>Investigation</h2>
        <table class="kv">
            <tr>
                <td>
                    <div class="label">Status</div>
                    <div class="v"><span class="pill" style="color: {{ $investigation['text'] }}; background-color: rgba({{ $investigation['rgb'] }}, .14); border: 1pt solid rgba({{ $investigation['rgb'] }}, .34);">{{ strtoupper($investigation['status']) }}</span></div>
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
            <div class="label" style="margin: 11pt 0 4pt 0;">Team notes</div>
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

<div class="note">
    This report was produced by automated analysis. Risk scores and verdicts are indicators to support a
    decision, not proof of intent, and a result can change as a site is updated or taken down. Review the
    findings above before taking action.
</div>

</body>
</html>