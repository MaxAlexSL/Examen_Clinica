<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    // affiliation_code no es asignable: se genera a partir del id (AF-000001).
    protected $fillable = [
        'document_type',
        'document_number',
        'first_names',
        'last_names',
        'birth_date',
        'affiliation_date',
        'phone',
        'email',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'affiliation_date' => 'date',
        ];
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'ACTIVO');
    }
}
