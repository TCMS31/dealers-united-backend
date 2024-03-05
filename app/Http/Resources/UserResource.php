<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The public shape of an account.
 *
 * The endpoints used to return the Eloquent model directly, which meant the
 * payload grew a field every time the users table did — the Fortify migration
 * alone adds `two_factor_secret` and `two_factor_recovery_codes`. Returning a
 * raw model also triggers Laravel's "freshly created model" rule, which turns
 * a plain `GET /user` into a 201 whenever the cached model happens to be the
 * one just inserted.
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
