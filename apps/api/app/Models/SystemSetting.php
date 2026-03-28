<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'group',
        'key',
        'value',
    ];

    protected function value(): Attribute
    {
        return Attribute::make(
            get: static fn (?string $value): mixed => $value === null ? null : json_decode($value, true),
            set: static fn (mixed $value): array => [
                'value' => $value === null ? null : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ],
        );
    }
}
