<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxResolution extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_profile_id',
        'name',
        'authorization_number',
        'series',
        'invoice_number_start',
        'invoice_number_end',
        'next_invoice_number',
        'starts_at',
        'ends_at',
        'technical_key',
        'legend',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'invoice_number_start' => 'integer',
            'invoice_number_end' => 'integer',
            'next_invoice_number' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function companyProfile(): BelongsTo
    {
        return $this->belongsTo(CompanyProfile::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }
}
