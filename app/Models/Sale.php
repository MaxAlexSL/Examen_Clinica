<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    // code no es asignable: se genera a partir del id (V-000001).
    protected $fillable = [
        'patient_id',
        'user_id',
        'payment_method',
        'total',
        'sold_at',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'sold_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    // Vendedor que registró la venta.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Ventas que el usuario puede ver (misma regla que SalePolicy::view, RN-12):
     * ventas.ver_todas → todas; ventas.ver → solo las suyas; sin permiso → ninguna.
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->can('ventas.ver_todas')) {
            return;
        }

        if ($user->can('ventas.ver')) {
            $query->where('user_id', $user->id);

            return;
        }

        $query->whereRaw('1 = 0');
    }
}
