<?php

namespace App\Services;

use App\Models\MessageCapsule;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Everything the application knows how to *do* with a capsule.
 *
 * The controller translates HTTP into calls on this class and back again; it
 * holds no rules of its own. Authorisation stays in the policy, validation in
 * the form request, and persistence here — so a queue job or an Artisan
 * command can seal and open capsules without going through a request.
 */
class MessageCapsuleService
{
    /**
     * A user's capsules, ordered by opening time.
     *
     * @param  int|null  $perPage  null returns the whole collection; an int
     *                             returns that page size, capped by config.
     * @return Collection<int, MessageCapsule>|LengthAwarePaginator
     */
    public function listFor(User $user, ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $query = $user->messageCapsules()->sealedOrder();

        if ($perPage === null) {
            return $query->get();
        }

        $max = (int) config('capsules.max_per_page', 100);

        return $query->paginate(max(1, min($perPage, $max)));
    }

    /**
     * Seal a new capsule for a user.
     *
     * @param  array{note: string, scheduled_opening_time: mixed}  $attributes
     */
    public function create(User $user, array $attributes): MessageCapsule
    {
        return $user->messageCapsules()->create([
            'note' => $attributes['note'],
            'scheduled_opening_time' => $attributes['scheduled_opening_time'],
            'is_opened' => false,
        ]);
    }

    /**
     * Reveal a capsule. Idempotent: opening an already-open capsule is a
     * no-op that still returns the capsule, so a retried PUT is safe.
     */
    public function open(MessageCapsule $capsule): MessageCapsule
    {
        if (! $capsule->is_opened) {
            $capsule->forceFill(['is_opened' => true])->save();
        }

        return $capsule;
    }
}
