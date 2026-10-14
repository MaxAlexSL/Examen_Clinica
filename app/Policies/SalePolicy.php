<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

/**
 * Autorización de ventas, solo por permisos (nunca por nombre de rol).
 * RN-12: ventas.ver_todas ve todas; ventas.ver solo las propias.
 * La misma regla se aplica a los listados con el scope Sale::visibleTo().
 */
class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ventas.ver') || $user->can('ventas.ver_todas');
    }

    public function view(User $user, Sale $sale): bool
    {
        if ($user->can('ventas.ver_todas')) {
            return true;
        }

        return $user->can('ventas.ver') && (int) $sale->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('ventas.crear');
    }
}
