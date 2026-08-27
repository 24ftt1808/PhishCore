<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}