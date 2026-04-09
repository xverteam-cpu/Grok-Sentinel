<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class IncidentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_type',
        'source_ip',
        'country_code',
        'city',
        'region',
        'user_agent',
        'metadata',
        'risk_score',
        'detected_at',
        'resolved_at',
        'is_reviewed',
    ];

    protected $casts = [
        'risk_score' => 'integer',
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
        'is_reviewed' => 'boolean',
    ];

    public function getMetadataAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            return json_decode(Crypt::decryptString($value), true);
        } catch (\Throwable $exception) {
            return null;
        }
    }

    public function setMetadataAttribute($value)
    {
        $this->attributes['metadata'] = Crypt::encryptString(json_encode($value));
    }
}
