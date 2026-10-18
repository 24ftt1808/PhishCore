<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\CheckExplainer;
use App\Services\ScanAdvice;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

/**
 * Downloads a scan as a PDF case report.
 *
 * Access mirrors the scan result page itself (only people who can open
 * /scan/{id} — the owner, the team, or the guest session that made it — can
 * download it). The investigation section follows the same rule as the on-screen
 * panel: signed-in users only, and names/notes are visible to team members only.
 */
class ScanPdfController extends Controller
{
    /** Same palette as the website: rgb for translucent fills, hex for text and strokes. */
    private const VERDICTS = [
        'phishing' => ['headline' => 'Phishing detected', 'rgb' => '248,113,113', 'hex' => '#f87171', 'icon' => 'alert'],
        'suspicious' => ['headline' => 'This looks suspicious', 'rgb' => '251,146,60', 'hex' => '#fb923c', 'icon' => 'alert'],
        'review' => ['headline' => 'Needs a manual review', 'rgb' => '56,189,248', 'hex' => '#38bdf8', 'icon' => 'info'],
        'clean' => ['headline' => 'This appears safe', 'rgb' => '52,211,153', 'hex' => '#34d399', 'icon' => 'check'],
    ];

    /** status => [text hex, rgb] */
    private const STATUS_COLORS = [
        'SAFE' => ['#6ee7b7', '52,211,153'],
        'LIVE' => ['#6ee7b7', '52,211,153'],
        'SUSPICIOUS' => ['#fdba74', '251,146,60'],
        'HIGH RISK' => ['#fca5a5', '248,113,113'],
        'DETECTED' => ['#fca5a5', '248,113,113'],
        'REVIEW' => ['#7dd3fc', '56,189,248'],
        'TAKEN DOWN' => ['#7dd3fc', '56,189,248'],
        'UNKNOWN' => ['#cbd5e1', '148,163,184'],
        'INFO' => ['#cbd5e1', '148,163,184'],
        'OFFLINE' => ['#cbd5e1', '148,163,184'],
    ];

    /** investigation status => [text hex, rgb] */
    private const INVESTIGATION_COLORS = [
        'active' => ['#7dd3fc', '56,189,248'],
        'completed' => ['#6ee7b7', '52,211,153'],
        'takedown_requested' => ['#fdba74', '251,146,60'],
        'takedown_confirmed' => ['#6ee7b7', '52,211,153'],
    ];

    private const INVESTIGATION_LABELS = [
        'active' => 'Active',
        'completed' => 'Completed',
        'takedown_requested' => 'Takedown requested',
        'takedown_confirmed' => 'Takedown confirmed',
    ];

    /** The three groups under "What we checked" (same titles, notes and colours as the result page). */
    private const GROUPS = [
        'warn' => ['title' => 'Warnings', 'note' => null, 'rgb' => '251,191,36'],
        'ok' => ['title' => 'No issues found', 'note' => 'Each of these checks found nothing wrong on its own. The overall result is based on all checks together.', 'rgb' => '52,211,153'],
        'info' => ['title' => 'Unavailable or for information', 'note' => '"Unavailable" means the check could not get an answer, for example because the website is offline. It is neither good nor bad, and it does not affect the score.', 'rgb' => '148,163,184'],
    ];

    /**
     * DomPDF ignores the SVG viewBox and draws every icon at 3/4 of its box, so the icon
     * coordinates (and line width) are enlarged by 4/3 to make them fill the box.
     */
    private const ICON_SCALE = 4 / 3;

    /** Heroicons paths, the same icons the result page uses. */
    private const TYPE_ICONS = [
        'url' => 'M21 12a9 9 0 11-18 0 9 9 0 0118 0zM3.6 9h16.8M3.6 15h16.8M12 3a13.5 13.5 0 010 18M12 3a13.5 13.5 0 000 18',
        'email' => 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75',
        'phone' => 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
        'screenshot' => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M18 22.5H6a2.25 2.25 0 01-2.25-2.25V3.75A2.25 2.25 0 016 1.5h12a2.25 2.25 0 012.25 2.25v16.5A2.25 2.25 0 0118 22.5zM10.5 8.25a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z',
        'file' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
    ];

    private const VERDICT_ICONS = [
        'check' => 'M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z',
        'info' => 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z',
        'alert' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
    ];

    private const CHECK_ICONS = [
        'SSL Certificate' => 'M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z',
        'Domain Age' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
        'URL Structure' => 'M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5',
        'Blacklist Database' => 'M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z',
        'Sender Domain Analysis' => 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75',
        'Phone Number Analysis' => 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
        'Screenshot Text Extraction' => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M18 22.5H6a2.25 2.25 0 01-2.25-2.25V3.75A2.25 2.25 0 016 1.5h12a2.25 2.25 0 012.25 2.25v16.5A2.25 2.25 0 0118 22.5zM10.5 8.25a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z',
    ];

    private const DEFAULT_CHECK_ICON = 'M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z';

    public function __invoke(Request $request, Report $report): Response
    {
        abort_unless($report->canBeViewedBy($request->user()), 404);

        $report->load(['analyses', 'ctiLookups', 'user', 'investigation.assignedUser', 'investigation.statusLogs.changedBy']);

        $analysis = $report->analyses->first();
        abort_if($analysis === null, 404, 'This scan has no completed analysis to export.');

        $verdict = self::VERDICTS[$analysis->verdict] ?? self::VERDICTS['clean'];

        // A message or file scan only looks at wording, sender, numbers and fingerprints, so
        // "nothing found" is not the same as "safe". Links keep the original wording. This is
        // the same wording as the result page.
        if (($analysis->verdict === 'clean' || ! isset(self::VERDICTS[$analysis->verdict])) && $report->type !== 'url') {
            $verdict['headline'] = match ($report->type) {
                'phone' => 'No reports found',
                'file' => 'No known threats found',
                default => 'No threats detected',
            };
            $verdict['caveat'] = match ($report->type) {
                'phone' => 'This is not a guarantee of safety. A new scam number can have no reports yet. Be careful with anything that asks for money, passwords or codes.',
                'file' => 'This is not a guarantee of safety. A brand-new scam file may not be known yet. Only install apps from the Play Store or App Store, and do not open files from people you do not know.',
                default => 'This is not a guarantee of safety. Be careful with anything that asks for money, passwords or codes.',
            };
        }
        $score = max(0, min(100, (int) $analysis->risk_score));

        $severity = match (true) {
            $analysis->verdict === 'phishing' && $score >= 80 => 'CRITICAL',
            $analysis->verdict === 'phishing' => 'HIGH',
            $analysis->verdict === 'suspicious' => 'MEDIUM',
            $analysis->verdict === 'review' => 'UNVERIFIED',
            default => 'LOW',
        };

        // same reference id as the scan result page
        $ref = 'PG-'.$report->created_at->format('Y-md').'-'.strtoupper(substr(md5((string) $report->id), 0, 5));

        $targetLabel = match ($report->type) {
            'email' => 'Reported sender email',
            'phone' => 'Reported phone number',
            'screenshot' => 'Uploaded screenshot',
            'file' => 'Uploaded file',
            default => 'Scanned URL',
        };
        $target = match ($report->type) {
            'email' => $report->sender_email,
            'phone' => $report->phone_number,
            'screenshot' => 'Image submitted for OCR analysis',
            'file' => $report->file_name ?: 'Uploaded file',
            default => $report->url,
        };

        // ---- the checks, in the same plain words and groups as "What we checked" on the result page
        $allChecks = collect($analysis->flags ?? [])->filter(fn ($check) => is_array($check))->values();

        $cards = $allChecks->map(function (array $check) {
            $name = (string) ($check['name'] ?? 'Check');
            $status = (string) ($check['status'] ?? 'SAFE');
            [$text, $rgb] = self::STATUS_COLORS[$status] ?? self::STATUS_COLORS['UNKNOWN'];

            return [
                'name' => $name,
                'title' => CheckExplainer::title($name),
                'technical' => CheckExplainer::hasFriendlyTitle($name) ? $name : null,
                'status' => $status,
                'label' => CheckExplainer::statusLabel($status),
                'group' => CheckExplainer::group($status),
                'points' => (int) ($check['points'] ?? 0),
                'message' => CheckExplainer::plain((string) ($check['message'] ?? '')),
                'raw' => (string) ($check['message'] ?? ''),
                'meaning' => CheckExplainer::meaning($name),
                'text' => $text,
                'rgb' => $rgb,
                'icon' => $this->pathIconUri(self::CHECK_ICONS[$name] ?? self::DEFAULT_CHECK_ICON, '#cbd5e1', 1.75),
            ];
        });

        $groups = [];
        foreach (self::GROUPS as $key => $def) {
            $members = $cards->where('group', $key)->values();
            if ($members->isEmpty()) {
                continue;
            }
            $more = $members->count() - 3;
            $groups[] = $def + [
                'key' => $key,
                'checks' => $members->all(),
                'count' => $members->count(),
                'peek' => $members->take(3)->pluck('title')->implode(', ').($more > 0 ? ' and '.$more.' more' : ''),
                'hex' => 'rgb('.$def['rgb'].')',
            ];
        }

        // ---- "Why this score?": how much of the score each check is responsible for
        $totalScore = max((int) $analysis->risk_score, 1);
        $breakdown = $cards->where('points', '>', 0)
            ->sortByDesc('points')
            ->map(function (array $card) use ($totalScore) {
                $pct = (int) round($card['points'] / $totalScore * 100);

                return [
                    'title' => $card['title'],
                    'pct' => $pct,
                    'width' => max(2, min(100, $pct)),
                    'color' => $pct >= 80 ? '#f87171' : ($pct >= 40 ? '#fb923c' : '#38bdf8'),
                    'text' => $pct >= 80 ? '#fca5a5' : ($pct >= 40 ? '#fdba74' : '#7dd3fc'),
                ];
            })
            ->values()
            ->all();

        $warnCount = $cards->where('points', '>', 0)->count();
        $topCard = $cards->sortByDesc('points')->first();
        $topReason = ($topCard['points'] ?? 0) > 0 ? $topCard['message'] : null;
        $aiSkipped = $cards->contains(fn ($c) => in_array($c['name'], ['AI Message Review', 'AI Page Review'], true) && $c['status'] === 'UNKNOWN');

        // ---- VirusTotal vendor results
        $cti = $report->ctiLookups->first();
        $vtStats = $cti?->raw_response['data']['attributes']['last_analysis_stats'] ?? null;
        $vt = null;
        if (is_array($vtStats)) {
            $vt = [
                'malicious' => (int) ($vtStats['malicious'] ?? 0),
                'suspicious' => (int) ($vtStats['suspicious'] ?? 0),
                'harmless' => (int) ($vtStats['harmless'] ?? 0),
                'undetected' => (int) ($vtStats['undetected'] ?? 0),
            ];
            $vt['total'] = array_sum($vt);
            $vt['flagged'] = $vt['malicious'] + $vt['suspicious'];
            $vt['source'] = (string) ($cti->source ?? 'VirusTotal');
            $vt['threat'] = $cti->threat_score ?? null;
            $vt['when'] = isset($cti->created_at) ? $cti->created_at->format('j F Y \a\t g:i A') : null;
            $vt = $vt['total'] > 0 ? $vt : null;
        }

        // ---- the strip of facts at the bottom of the verdict card (same labels as the result page)
        $facts = array_values(array_filter([
            ['Checks with warnings', $warnCount.' of '.$cards->count(), $warnCount > 0 ? $verdict['hex'] : '#ffffff'],
            $vt ? ['Security companies', $vt['flagged'].' of '.$vt['total'].' flagged', $vt['flagged'] > 0 ? '#fca5a5' : '#6ee7b7'] : null,
            $analysis->domain_age_days !== null ? ['Website age', number_format($analysis->domain_age_days).' days', $analysis->domain_age_days < 30 ? '#fdba74' : '#ffffff'] : null,
            $analysis->duration_ms !== null ? ['Time taken', round($analysis->duration_ms / 1000, 1).'s', '#ffffff'] : null,
        ]));

        $technical = [
            ['Scan reference ID', $ref],
            ['Report type', strtoupper((string) $report->type)],
            ['Verdict', strtoupper((string) $analysis->verdict)],
            ['Risk score', $score.' / 100'],
            ['Domain age', $analysis->domain_age_days !== null ? $analysis->domain_age_days.' days' : 'Unavailable'],
            ['IP address', $analysis->ip_address ?? 'Unavailable'],
            ['IP reputation', $analysis->ip_reputation ?? 'Unavailable'],
            ['Country', $analysis->country ?? 'Unavailable'],
            ['Scanned by', $report->user?->name ?? 'Guest (unregistered)'],
            ['Scan duration', $analysis->duration_ms !== null ? round($analysis->duration_ms / 1000, 2).'s' : 'Not recorded'],
        ];

        $chain = collect($analysis->redirect_chain ?? [])->filter(fn ($hop) => is_string($hop))->values();
        $chain = $chain->count() > 1 ? $chain->all() : [];

        $html = view('scan.pdf', [
            'ref' => $ref,
            'verdict' => $verdict,
            'type' => (string) $report->type,
            'gauge' => $this->gaugeDataUri($score, $verdict['hex']),
            'heroGlow' => $this->heroGlowDataUri($verdict['rgb']),
            'ic' => [
                'verdict' => $this->tileIconUri(self::VERDICT_ICONS[$verdict['icon']], $verdict['hex'], $verdict['rgb']),
                'target' => $this->pathIconUri(self::TYPE_ICONS[$report->type] ?? self::TYPE_ICONS['url'], '#94a3b8', 1.75),
                'vt' => $this->tileIconUri(self::VERDICT_ICONS['check'], '#7dd3fc', '56,189,248'),
                'advice' => $this->tileIconUri(self::DEFAULT_CHECK_ICON, '#fdba74', '251,146,60', 1.5),
                'tech' => $this->pathIconUri('M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5', '#7dd3fc', 2),
            ],
            'scannedBy' => $report->user?->name,
            'bg' => $this->backgroundDataUri(),
            'score' => $score,
            'severity' => $severity,
            'scannedAt' => $report->created_at->format('j F Y \a\t g:i A'),
            'targetLabel' => $targetLabel,
            'target' => $target,
            'topReason' => $topReason,
            'aiSkipped' => $aiSkipped,
            'facts' => $facts,
            'summary' => CheckExplainer::summary($allChecks),
            'groups' => $groups,
            'breakdown' => $breakdown,
            'hasScore' => $score > 0 && count($breakdown) > 0,
            'vt' => $vt,
            'technical' => $technical,
            'chain' => $chain,
            'raw' => $cards->all(),
            'investigation' => $this->investigationSection($request, $report),
            'advice' => ScanAdvice::for((string) $report->type, (string) $analysis->verdict),
            'generatedAt' => now()->format('j F Y \a\t g:i A'),
            'logo' => $this->logoDataUri(),
        ])->render();

        return response($this->renderPdf($html), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="PhishCore-'.$ref.'.pdf"',
        ]);
    }

    /**
     * Signed-in users see the status and timeline; only team members also see
     * who did what and the internal notes. Guests get no investigation section.
     */
    private function investigationSection(Request $request, Report $report): ?array
    {
        $user = $request->user();
        $investigation = $report->investigation;

        if ($user === null || $investigation === null) {
            return null;
        }

        $isTeam = (bool) $user->is_team_member;

        $timeline = $investigation->statusLogs
            ->sortBy('created_at')
            ->map(fn ($log) => [
                'label' => self::INVESTIGATION_LABELS[$log->status] ?? ucfirst(str_replace('_', ' ', (string) $log->status)),
                'text' => (self::INVESTIGATION_COLORS[$log->status] ?? self::INVESTIGATION_COLORS['active'])[0],
                'when' => $log->created_at->format('j F Y \a\t g:i A'),
                'by' => $isTeam ? $log->changedBy?->name : null,
            ])
            ->values()
            ->all();

        return [
            'status' => self::INVESTIGATION_LABELS[$investigation->status] ?? ucfirst(str_replace('_', ' ', (string) $investigation->status)),
            'text' => (self::INVESTIGATION_COLORS[$investigation->status] ?? self::INVESTIGATION_COLORS['active'])[0],
            'rgb' => (self::INVESTIGATION_COLORS[$investigation->status] ?? self::INVESTIGATION_COLORS['active'])[1],
            'assignee' => $isTeam ? ($investigation->assignedUser?->name ?? 'Unassigned') : null,
            'notes' => $isTeam && filled($investigation->notes) ? $investigation->notes : null,
            'resolved' => $investigation->resolved_at?->format('j F Y'),
            'timeline' => $timeline,
        ];
    }

    /** The half-circle risk gauge from the result page, drawn as a small SVG. */
    private function gaugeDataUri(int $score, string $hex): string
    {
        $angle = $score / 100 * M_PI;
        $x = round(100 - 80 * cos($angle), 2);
        $y = round(100 - 80 * sin($angle), 2);

        $value = $score > 0
            ? '<path d="M 20 100 A 80 80 0 0 1 '.$x.' '.$y.'" fill="none" stroke="'.$hex.'" stroke-width="14" stroke-linecap="round"/>'
            : '';

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="112" viewBox="0 0 200 112">'
            .'<path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="#2b3b5c" stroke-width="14" stroke-linecap="round"/>'
            .$value
            .'</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * One of the website's Heroicons as a small image. DomPDF draws curved SVG paths slightly
     * off (smaller, shifted and skewed), so each path is first rewritten as short straight
     * segments, which it draws exactly.
     */
    private function pathIconUri(string $path, string $hex, float $stroke = 1.75): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="'.$hex.'" stroke-width="'.round($stroke * self::ICON_SCALE, 3).'" stroke-linecap="round" stroke-linejoin="round">'
            .'<path d="'.$this->flattenPath($path).'"/></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /** Turns an SVG path (lines, curves and arcs) into absolute M / L / Z segments only. */
    private function flattenPath(string $d, float $glyphScale = 1.0, float $glyphOffset = 0.0): string
    {
        $len = strlen($d);
        $i = 0;
        $out = [];
        $cx = $cy = $sx = $sy = 0.0;
        $lastC = null; // last cubic control point, for the S command
        $cmd = '';

        $skip = function () use (&$i, $len, $d) {
            while ($i < $len && ($d[$i] === ' ' || $d[$i] === ',' || $d[$i] === "\n" || $d[$i] === "\t")) {
                $i++;
            }
        };
        $num = function () use (&$i, $len, $d, $skip): float {
            $skip();
            preg_match('/\G-?(?:\d+\.?\d*|\.\d+)(?:[eE][-+]?\d+)?/', $d, $m, 0, $i);
            $i += strlen($m[0]);

            return (float) $m[0];
        };
        $flag = function () use (&$i, $d, $skip): int {
            $skip();

            return (int) $d[$i++];
        };
        $fmt = fn (float $v) => rtrim(rtrim(number_format($v, 3, '.', ''), '0'), '.');
        $pt = function (float $x, float $y) use ($fmt, $glyphScale, $glyphOffset) {
            return $fmt(($x * $glyphScale + $glyphOffset) * self::ICON_SCALE).' '.$fmt(($y * $glyphScale + $glyphOffset) * self::ICON_SCALE);
        };

        while (true) {
            $skip();
            if ($i >= $len) {
                break;
            }
            if (ctype_alpha($d[$i])) {
                $cmd = $d[$i++];
            } elseif ($cmd === 'M') {
                $cmd = 'L';
            } elseif ($cmd === 'm') {
                $cmd = 'l';
            }

            $rel = ctype_lower($cmd);
            $c = strtoupper($cmd);
            $ox = $rel ? $cx : 0.0;
            $oy = $rel ? $cy : 0.0;

            switch ($c) {
                case 'M':
                    $cx = $ox + $num();
                    $cy = $oy + $num();
                    $sx = $cx;
                    $sy = $cy;
                    $out[] = 'M '.$pt($cx, $cy);
                    $lastC = null;
                    break;
                case 'L':
                    $cx = $ox + $num();
                    $cy = $oy + $num();
                    $out[] = 'L '.$pt($cx, $cy);
                    $lastC = null;
                    break;
                case 'H':
                    $cx = $ox + $num();
                    $out[] = 'L '.$pt($cx, $cy);
                    $lastC = null;
                    break;
                case 'V':
                    $cy = $oy + $num();
                    $out[] = 'L '.$pt($cx, $cy);
                    $lastC = null;
                    break;
                case 'C':
                case 'S':
                    if ($c === 'C') {
                        $x1 = $ox + $num();
                        $y1 = $oy + $num();
                    } else {
                        $x1 = $lastC ? 2 * $cx - $lastC[0] : $cx;
                        $y1 = $lastC ? 2 * $cy - $lastC[1] : $cy;
                    }
                    $x2 = $ox + $num();
                    $y2 = $oy + $num();
                    $x = $ox + $num();
                    $y = $oy + $num();
                    for ($k = 1; $k <= 10; $k++) {
                        $t = $k / 10;
                        $u = 1 - $t;
                        $px = $u ** 3 * $cx + 3 * $u * $u * $t * $x1 + 3 * $u * $t * $t * $x2 + $t ** 3 * $x;
                        $py = $u ** 3 * $cy + 3 * $u * $u * $t * $y1 + 3 * $u * $t * $t * $y2 + $t ** 3 * $y;
                        $out[] = 'L '.$pt($px, $py);
                    }
                    $lastC = [$x2, $y2];
                    $cx = $x;
                    $cy = $y;
                    break;
                case 'Q':
                    $x1 = $ox + $num();
                    $y1 = $oy + $num();
                    $x = $ox + $num();
                    $y = $oy + $num();
                    for ($k = 1; $k <= 8; $k++) {
                        $t = $k / 8;
                        $u = 1 - $t;
                        $out[] = 'L '.$pt($u * $u * $cx + 2 * $u * $t * $x1 + $t * $t * $x, $u * $u * $cy + 2 * $u * $t * $y1 + $t * $t * $y);
                    }
                    $cx = $x;
                    $cy = $y;
                    $lastC = null;
                    break;
                case 'A':
                    $rx = abs($num());
                    $ry = abs($num());
                    $rot = deg2rad($num());
                    $large = $flag();
                    $sweep = $flag();
                    $x = $ox + $num();
                    $y = $oy + $num();
                    foreach ($this->arcPoints($cx, $cy, $rx, $ry, $rot, $large, $sweep, $x, $y) as [$px, $py]) {
                        $out[] = 'L '.$pt($px, $py);
                    }
                    $cx = $x;
                    $cy = $y;
                    $lastC = null;
                    break;
                case 'Z':
                    $out[] = 'Z';
                    $cx = $sx;
                    $cy = $sy;
                    $lastC = null;
                    break;
                default:
                    break 2; // unknown command: stop here rather than loop forever
            }
        }

        return implode(' ', $out);
    }

    /** Points along an SVG elliptical arc (endpoint form), the end point included. */
    private function arcPoints(float $x1, float $y1, float $rx, float $ry, float $phi, int $large, int $sweep, float $x2, float $y2): array
    {
        if ($rx == 0.0 || $ry == 0.0 || ($x1 == $x2 && $y1 == $y2)) {
            return [[$x2, $y2]];
        }

        $cos = cos($phi);
        $sin = sin($phi);
        $dx = ($x1 - $x2) / 2;
        $dy = ($y1 - $y2) / 2;
        $x1p = $cos * $dx + $sin * $dy;
        $y1p = -$sin * $dx + $cos * $dy;

        $lambda = ($x1p ** 2) / ($rx ** 2) + ($y1p ** 2) / ($ry ** 2);
        if ($lambda > 1) {
            $rx *= sqrt($lambda);
            $ry *= sqrt($lambda);
        }

        $num = ($rx ** 2) * ($ry ** 2) - ($rx ** 2) * ($y1p ** 2) - ($ry ** 2) * ($x1p ** 2);
        $den = ($rx ** 2) * ($y1p ** 2) + ($ry ** 2) * ($x1p ** 2);
        $coef = ($large === $sweep ? -1 : 1) * sqrt(max(0, $num / $den));
        $cxp = $coef * $rx * $y1p / $ry;
        $cyp = -$coef * $ry * $x1p / $rx;
        $ccx = $cos * $cxp - $sin * $cyp + ($x1 + $x2) / 2;
        $ccy = $sin * $cxp + $cos * $cyp + ($y1 + $y2) / 2;

        $theta1 = atan2(($y1p - $cyp) / $ry, ($x1p - $cxp) / $rx);
        $theta2 = atan2((-$y1p - $cyp) / $ry, (-$x1p - $cxp) / $rx);
        $delta = $theta2 - $theta1;
        if ($sweep === 0 && $delta > 0) {
            $delta -= 2 * M_PI;
        } elseif ($sweep === 1 && $delta < 0) {
            $delta += 2 * M_PI;
        }

        $steps = max(6, (int) ceil(abs($delta) / (M_PI / 12)));
        $points = [];
        for ($k = 1; $k <= $steps; $k++) {
            $a = $theta1 + $delta * $k / $steps;
            $points[] = [
                $ccx + $rx * cos($a) * $cos - $ry * sin($a) * $sin,
                $ccy + $rx * cos($a) * $sin + $ry * sin($a) * $cos,
            ];
        }

        return $points;
    }

    /**
     * A rounded icon tile (tinted square with a thin border and the icon in the middle) drawn as
     * one image, so the icon is centred exactly. $rgb is "r,g,b" for the tint, $hex the icon colour.
     */
    private function tileIconUri(string $path, string $hex, string $rgb, float $stroke = 1.75): string
    {
        $k = self::ICON_SCALE;
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">'
            .'<rect x="'.round(.5 * $k, 3).'" y="'.round(.5 * $k, 3).'" width="'.round(23 * $k, 3).'" height="'.round(23 * $k, 3).'" rx="'.round(7 * $k, 3).'" ry="'.round(7 * $k, 3).'"'
            .' fill="rgb('.$rgb.')" fill-opacity="0.13" stroke="rgb('.$rgb.')" stroke-opacity="0.34" stroke-width="'.round(.9 * $k, 3).'"/>'
            .'<path d="'.$this->flattenPath($path, .5, 6).'" fill="none" stroke="'.$hex.'" stroke-width="'.round($stroke * .5 * $k, 3).'" stroke-linecap="round" stroke-linejoin="round"/>'
            .'</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * The soft verdict-coloured glow behind the verdict card (the website draws it with a CSS
     * gradient, which DomPDF cannot). A small transparent PNG per colour, drawn once with GD
     * and cached. Returns null when GD is not available, and the card is then a flat panel.
     */
    private function heroGlowDataUri(string $rgb): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $file = storage_path('app/dompdf/pdf-hero-'.str_replace(',', '-', $rgb).'-v1.png');

        if (! is_file($file)) {
            File::ensureDirectoryExists(dirname($file));

            [$cr, $cg, $cb] = array_map('intval', explode(',', $rgb));
            $w = 240;
            $h = 120;
            $image = imagecreatetruecolor($w, $h);
            imagealphablending($image, false);
            imagesavealpha($image, true);

            // linear-gradient(135deg, verdict colour 16%, navy 66% at 58%) with the verdict colour fading out
            $from = [$cr, $cg, $cb, .16];
            $to = [14, 25, 50, .66];

            for ($y = 0; $y < $h; $y++) {
                for ($x = 0; $x < $w; $x++) {
                    $t = min(1, (($x / $w) + ($y / $h)) / 2 / .58);
                    $a = $from[3] + ($to[3] - $from[3]) * $t;
                    $r = ($from[0] * $from[3] * (1 - $t) + $to[0] * $to[3] * $t) / max($a, .001);
                    $g = ($from[1] * $from[3] * (1 - $t) + $to[1] * $to[3] * $t) / max($a, .001);
                    $b = ($from[2] * $from[3] * (1 - $t) + $to[2] * $to[3] * $t) / max($a, .001);

                    imagesetpixel($image, $x, $y, imagecolorallocatealpha($image, (int) round($r), (int) round($g), (int) round($b), (int) round(127 * (1 - $a))));
                }
            }

            imagepng($image, $file);
        }

        return 'data:image/png;base64,'.base64_encode(file_get_contents($file));
    }

    /**
     * The website's navy background with its soft blue glows, drawn once with GD and cached.
     * Returns null when GD is not available, and the PDF then falls back to a flat navy page.
     */
    private function backgroundDataUri(): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $file = storage_path('app/dompdf/pdf-background-v1.png');

        if (! is_file($file)) {
            File::ensureDirectoryExists(dirname($file));

            $w = 300;
            $h = 424;
            $image = imagecreatetruecolor($w, $h);
            // [centre x, centre y, radius, rgb, strength]
            $glows = [
                [.20, .00, .75, [37, 99, 235], .45],
                [.85, .10, .60, [14, 165, 233], .22],
                [.50, 1.05, .80, [30, 64, 175], .30],
            ];

            for ($y = 0; $y < $h; $y++) {
                for ($x = 0; $x < $w; $x++) {
                    $fx = $x / $w;
                    $fy = $y / $h;
                    [$r, $g, $b] = [5, 11, 28];

                    foreach ($glows as [$cx, $cy, $radius, $rgb, $strength]) {
                        $d = sqrt(($fx - $cx) ** 2 + (($fy - $cy) * 1.41) ** 2) / $radius;

                        if ($d < 1) {
                            $t = 1 - $d;
                            $t = $t * $t * (3 - 2 * $t) * $strength;
                            $r += ($rgb[0] - $r) * $t;
                            $g += ($rgb[1] - $g) * $t;
                            $b += ($rgb[2] - $b) * $t;
                        }
                    }

                    imagesetpixel($image, $x, $y, imagecolorallocate($image, (int) $r, (int) $g, (int) $b));
                }
            }

            imagepng($image, $file);
        }

        return 'data:image/png;base64,'.base64_encode(file_get_contents($file));
    }

    private function logoDataUri(): ?string
    {
        $path = public_path('phishcore-logo-icon.png');

        return is_file($path) ? 'data:image/png;base64,'.base64_encode(file_get_contents($path)) : null;
    }

    /**
     * Registers the Manrope font files in resources/fonts (the same typeface as the website).
     * If they are missing or cannot be read, the PDF quietly falls back to DejaVu Sans.
     */
    private function registerFonts(Dompdf $dompdf): void
    {
        $files = [
            'normal' => resource_path('fonts/Manrope-400.ttf'),
            'bold' => resource_path('fonts/Manrope-700.ttf'),
        ];

        foreach ($files as $path) {
            if (! is_file($path)) {
                return;
            }
        }

        try {
            $metrics = $dompdf->getFontMetrics();

            if (isset($metrics->getFontFamilies()['manrope'])) {
                return;
            }

            foreach ($files as $weight => $path) {
                $metrics->registerFont(
                    ['family' => 'Manrope', 'style' => 'normal', 'weight' => $weight],
                    str_replace('\\', '/', $path)
                );
            }
        } catch (\Throwable) {
            // keep going with the default font
        }
    }

    private function renderPdf(string $html): string
    {
        $cache = storage_path('app/dompdf');
        File::ensureDirectoryExists($cache);

        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('tempDir', $cache);
        $options->set('fontCache', $cache);
        // the only place the PDF library may read local files from (the bundled Manrope font)
        $options->set('chroot', [resource_path('fonts')]);

        $dompdf = new Dompdf($options);
        $this->registerFonts($dompdf);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        return $dompdf->output();
    }
}