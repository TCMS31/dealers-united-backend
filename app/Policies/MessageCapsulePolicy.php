<?php

namespace App\Policies;

use App\Models\MessageCapsule;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MessageCapsulePolicy
{
    public function viewAny(User $user, User $owner): Response
    {
        return $user->is($owner)
            ? Response::allow()
            : Response::deny('You are not authorized to access these message capsules.');
    }

    public function create(User $user, User $owner): Response
    {
        return $user->is($owner)
            ? Response::allow()
            : Response::deny('You are not authorized to create message capsules for this user.');
    }

    /**
     * A capsule may be read once its opening time has passed. Before that the
     * owner still sees it in the listing, masked.
     */
    public function view(User $user, MessageCapsule $messageCapsule): Response
    {
        if (! $this->owns($user, $messageCapsule)) {
            return $this->notOwner();
        }

        return $messageCapsule->openingTimePassed()
            ? Response::allow()
            : Response::deny('Message capsule cannot be opened yet - time remaining.');
    }

    public function open(User $user, MessageCapsule $messageCapsule): Response
    {
        return $this->view($user, $messageCapsule);
    }

    /**
     * Compare foreign keys rather than `$user->is($messageCapsule->user)`,
     * which lazily loaded the owning User on every authorisation check — one
     * extra query per request, and N extra on any future collection check.
     */
    private function owns(User $user, MessageCapsule $messageCapsule): bool
    {
        return $messageCapsule->user_id !== null
            && (int) $messageCapsule->user_id === (int) $user->getKey();
    }

    private function notOwner(): Response
    {
        return Response::deny('You are not authorized to access this message capsule.');
    }
}
