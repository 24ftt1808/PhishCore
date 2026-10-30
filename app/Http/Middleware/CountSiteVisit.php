<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Counts how many different people open pages on the site each day, for the admin Usage page.
 *
 * Privacy: nothing that names a person is stored. A visitor is a one-way code made from the address, the
 * browser and the day, so it cannot be reversed and changes every day.
 * Only real page loads are counted: not scripts, JSON calls, errors, redirects or search-engine bots.
 * A failure here is only reported to the log, so counting can never break a page.
 */
class CountSiteVisit
{
    private const BOTS = '/bot|crawl|spider|slurp|curl|wget|python|httpclient|headless|preview|monitor|uptime|facebookexternalhit|lighthouse/i';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            if ($this->countable($request, $response)) {
                self::record($request);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $response;
    }

    private function countable(Request $request, Response $response): bool
    {
        $agent = (string) $request->userAgent();

        return $request->isMethod('GET')
            && ! $request->expectsJson()
            && ! $request->ajax()
            && $response->getStatusCode() === 200
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            && $agent !== ''
            && ! preg_match(self::BOTS, $agent);
    }

    public static function code(Request $request, string $day): string
    {
        return substr(hash_hmac('sha256', $request->ip().'|'.$request->userAgent().'|'.$day, (string) config('app.key')), 0, 32);
    }

    public static function record(Request $request): void
    {
        $day = now()->toDateString();
        $where = ['day' => $day, 'visitor_hash' => self::code($request, $day)];
        $member = $request->user() !== null;

        $bump = fn () => DB::table('site_visits')->where($where)->increment('views', 1, $member ? ['is_member' => true] : []);

        if ($bump() > 0) {
            return;
        }

        try {
            DB::table('site_visits')->insert($where + ['is_member' => $member, 'views' => 1, 'created_at' => now()]);
        } catch (QueryException) {
            // two pages of the same new visitor arrived at the same moment: the other one inserted first
            $bump();
        }
    }
}
