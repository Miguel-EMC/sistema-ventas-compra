<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'symbol',
        'decimals',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'decimals' => 'integer',
            'is_default' => 'boolean',
        ];
    }
}
