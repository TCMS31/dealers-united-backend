<?php

namespace App\Providers;

use App\Models\MessageCapsule;
use App\Policies\MessageCapsulePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Registered explicitly rather than left to convention discovery, so that
     * the mapping is greppable and survives a rename.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        MessageCapsule::class => MessageCapsulePolicy::class,
    ];
}
