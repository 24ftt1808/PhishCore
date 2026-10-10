<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\BruneiScamAlert;
use App\Services\CommunityStats;
class Analysis extends Model
{
    use HasFactory;
  protected $fillable = [
    'report_id',
    'whois_data',
    'domain_age_days',
    'url_syntax_score',
    'ip_address',
    'ip_reputation',
    'country',
    'redirect_chain',
    'verdict',
    'flags',
    'risk_score',
    'duration_ms'
];
protected $casts = [
    'whois_data' => 'array',
    'redirect_chain' => 'array',
    'flags' => 'array',
];
    protected static function booted(): void
    {
        // A phishing scan that imitates a Brunei brand rings the team's bell.
        static::created(fn (Analysis $analysis) => BruneiScamAlert::notifyFor($analysis));

        // The Analytics "Community" numbers are recalculated on the next page load.
        static::saved(fn () => CommunityStats::forget());
        static::deleted(fn () => CommunityStats::forget());
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}