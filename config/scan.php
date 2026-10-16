<?php

/*
|--------------------------------------------------------------------------
| Scan limits
|--------------------------------------------------------------------------
|
| How many scans one person may start. Guests are counted by IP address and
| signed-in users by account. Every scan uses the free quotas of outside
| services (VirusTotal, Google Safe Browsing, OCR, AI), so the limits keep
| one visitor from using them all up. Raise them in .env if needed.
|
*/

return [
    'guest_per_minute' => (int) env('SCAN_GUEST_PER_MINUTE', 3),
    'guest_per_hour' => (int) env('SCAN_GUEST_PER_HOUR', 10),
    'user_per_minute' => (int) env('SCAN_USER_PER_MINUTE', 6),
    'user_per_hour' => (int) env('SCAN_USER_PER_HOUR', 60),

    // A link that someone already scanned this many hours ago gets that result back instead of a new
    // scan, so repeat scans are instant and use none of the outside quotas. 0 turns this off.
    'reuse_hours' => (int) env('SCAN_REUSE_HOURS', 6),

    // A saved result is only handed back if it was made after the scan rules last changed, so a fix to the
    // rules shows up on the very next scan instead of after the reuse hours run out. "Last changed" is the
    // time the AnalysisEngine file was last written (a deploy that changes it starts everything fresh).
    // Set SCAN_REUSE_RULES_CHECK=false to turn this off; rules_file points the check at another file (tests).
    'reuse_rules_check' => (bool) env('SCAN_REUSE_RULES_CHECK', true),
    'rules_file' => null,
];
