<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditNote extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'sale_return_id',
        'sale_id',
        'invoice_id',
        'tax_resolution_id',
        'user_id',
        'status',
        'sequence_number',
        'credit_note_number',
        'invoice_number_reference',
        'authorization_number_snapshot',
        'company_name_snapshot',
        'company_tax_id_snapshot',
        'customer_name_snapshot',
        'customer_document_type_snapshot',
        'customer_document_number_snapshot',
        'reason',
        'subtotal',
        'tax_total',
        'grand_total',
        'currency_code',
        'footer',
        'legend',
        'issued_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'sequence_number' => 'integer',
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'issued_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function taxResolution(): BelongsTo
    {
        return $this->belongsTo(TaxResolution::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class);
    }
}
