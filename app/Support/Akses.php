<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class Akses
{
    /**
     * Apakah pengguna ini boleh membuka route tersebut?
     * Dibaca dari middleware "role:..." pada route, jadi menu selalu sama dengan aturan akses yang sebenarnya.
     */
    public static function bisa(?User $user, string $namaRoute): bool
    {
        if (! $user) {
            return false;
        }

        $route = Route::getRoutes()->getByName($namaRoute);

        if (! $route) {
            return false;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'role:')) {
                $diizinkan = explode(',', substr($middleware, 5));

                if (! in_array($user->role->value, $diizinkan, true)) {
                    return false;
                }
            }
        }

        return true;
    }
}