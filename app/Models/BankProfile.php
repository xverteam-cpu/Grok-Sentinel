<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'country_code',
        'bank_name',
        'branch_code',
        'account_number',
        'routing_number',
        'account_holder',
    ];

    protected function casts(): array
    {
        return [
            'bank_name' => 'encrypted',
            'branch_code' => 'encrypted',
            'account_number' => 'encrypted',
            'routing_number' => 'encrypted',
            'account_holder' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}