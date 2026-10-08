<?php

namespace App\Http\Controllers;

use App\Models\Report;
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

    public function __invoke(Request $request, Report $report): Response
    {
        abort_unless($report->canBeViewedBy($request->user()), 404);

        $report->load(['analyses', 'ctiLookups', 'user', 'investigation.assignedUser', 'investigation.statusLogs.changedBy']);

        $analysis = $report->analyses->first();
        abort_if($analysis === null, 404, 'This scan has no completed analysis to export.');

        $verdict = self::VERDICTS[$analysis->verdict] ?? self::VERDICTS['clean'];

        // A message scan only looks at wording, sender and numbers, so "nothing found" is
        // not the same as "safe". Links keep the original wording.
        if (($analysis->verdict === 'clean' || ! isset(self::VERDICTS[$analysis->verdict])) && $report->type !== 'url') {
            $verdict['headline'] = 'No threats detected';
            $verdict['caveat'] = 'This is not a guarantee of safety. Be careful with anything that asks for money, passwords or codes.';
        }
        $score = (int) $analysis->risk_score;

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
            default => 'Scanned URL',
        };
        $target = match ($report->type) {
            'email' => $report->sender_email,
            'phone' => $report->phone_number,
            'screenshot' => 'Image submitted for OCR analysis',
            default => $report->url,
        };

        $checks = collect($analysis->flags ?? [])
            ->filter(fn ($check) => is_array($check))
            ->map(function (array $check) {
                $status = (string) ($check['status'] ?? 'SAFE');
                [$text, $rgb] = self::STATUS_COLORS[$status] ?? self::STATUS_COLORS['UNKNOWN'];

                return [
                    'name' => (string) ($check['name'] ?? 'Check'),
                    'status' => $status,
                    'points' => (int) ($check['points'] ?? 0),
                    'message' => (string) ($check['message'] ?? ''),
                    'text' => $text,
                    'rgb' => $rgb,
                ];
            })
            ->sortByDesc('points')
            ->values();

        $flagged = $checks->where('points', '>', 0);
        $topReason = $flagged->first()['message'] ?? null;

        $vtStats = $report->ctiLookups->first()?->raw_response['data']['attributes']['last_analysis_stats'] ?? null;
        $vt = null;
        if (is_array($vtStats)) {
            $vt = [
                'malicious' => (int) ($vtStats['malicious'] ?? 0),
                'suspicious' => (int) ($vtStats['suspicious'] ?? 0),
                'harmless' => (int) ($vtStats['harmless'] ?? 0),
                'undetected' => (int) ($vtStats['undetected'] ?? 0),
            ];
            $vt['total'] = array_sum($vt);
            $vt = $vt['total'] > 0 ? $vt : null;
        }

        $facts = array_values(array_filter([
            ['Checks flagged', $flagged->count().' of '.$checks->count(), $flagged->count() > 0],
            $vt ? ['Security vendors', ($vt['malicious'] + $vt['suspicious']).' of '.$vt['total'].' flagged', ($vt['malicious'] + $vt['suspicious']) > 0] : null,
            $analysis->domain_age_days !== null ? ['Domain age', number_format($analysis->domain_age_days).' days'] : null,
            $analysis->duration_ms !== null ? ['Scan time', round($analysis->duration_ms / 1000, 1).'s'] : null,
        ]));

        $technical = [
            ['IP address', $analysis->ip_address ?? 'Unavailable'],
            ['IP reputation', $analysis->ip_reputation ?? 'Unavailable'],
            ['Country', $analysis->country ?? 'Unavailable'],
            ['Scanned by', $report->user?->name ?? 'Guest (unregistered)'],
        ];

        $chain = collect($analysis->redirect_chain ?? [])->filter(fn ($hop) => is_string($hop))->values();
        $chain = $chain->count() > 1 ? $chain->all() : [];

        $investigation = $this->investigationSection($request, $report);

        $html = view('scan.pdf', [
            'ref' => $ref,
            'verdict' => $verdict,
            'gauge' => $this->gaugeDataUri(max(0, min(100, $score)), $verdict['hex']),
            'icon' => $this->iconDataUri($verdict['icon'], $verdict['hex']),
            'globe' => $this->globeDataUri(),
            'scannedBy' => $report->user?->name,
            'bg' => $this->backgroundDataUri(),
            'score' => max(0, min(100, $score)),
            'severity' => $severity,
            'scannedAt' => $report->created_at->format('j F Y \a\t g:i A'),
            'targetLabel' => $targetLabel,
            'target' => $target,
            'topReason' => $topReason,
            'facts' => $facts,
            'checks' => $checks->all(),
            'vt' => $vt,
            'technical' => $technical,
            'chain' => $chain,
            'investigation' => $investigation,
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

    private function globeDataUri(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'
            .'<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3c2.6 2.7 3.9 5.7 3.9 9s-1.3 6.3-3.9 9c-2.6-2.7-3.9-5.7-3.9-9S9.4 5.7 12 3z"/></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    private function iconDataUri(string $kind, string $hex): string
    {
        $mark = match ($kind) {
            'check' => '<path d="M7.5 12.5l3 3 6-6.5"/>',
            'info' => '<path d="M12 11v5.5"/><path d="M12 7.6v.2"/>',
            default => '<path d="M12 7.5v5.5"/><path d="M12 16.4v.2"/>',
        };

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="'.$hex.'" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'
            .'<circle cx="12" cy="12" r="9"/>'.$mark.'</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
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